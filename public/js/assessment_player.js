/**
 * Assessment Player JavaScript
 * Handles assessment taking functionality including question navigation,
 * answer saving, timer management, and submission
 */

class AssessmentPlayer {
    constructor() {
        console.log('AssessmentPlayer constructor called');
        
        // Check if we're resuming an existing session
        this.checkForExistingSession();
        
        this.currentQuestion = 1;
        this.totalQuestions = window.assessmentData.totalQuestions;
        this.attemptId = window.assessmentData.attemptId;
        this.answers = {};
        this.timeRemaining = window.assessmentData.timeRemaining;
        this.timer = null;
        this.isSubmitting = false;
        this.submitModal = null; // Store modal instance
        this.charCountTimeout = null; // For character count debouncing
        
        // New properties for robust error handling
        this.offlineMode = false;
        this.pendingAnswers = new Map(); // Store answers when offline
        this.lastServerSync = Date.now();
        this.syncInterval = null;
        this.recoveryAttempts = 0;
        this.maxRecoveryAttempts = 3;
        
        console.log('AssessmentPlayer initialized with:', {
            currentQuestion: this.currentQuestion,
            totalQuestions: this.totalQuestions,
            attemptId: this.attemptId,
            timeRemaining: this.timeRemaining
        });
        
        // Debug assessment data
        console.log('Full assessment data:', window.assessmentData.assessment);
        console.log('Questions count:', window.assessmentData.assessment.selected_questions?.length || 0);
        if (window.assessmentData.assessment.selected_questions) {
            console.log('First question sample:', window.assessmentData.assessment.selected_questions[0]);
        }
        
        this.init();
    }

    // Check for existing session and attempt recovery
    checkForExistingSession() {
        const savedSession = localStorage.getItem(`assessment_session_${this.attemptId}`);
        if (savedSession) {
            try {
                const sessionData = JSON.parse(savedSession);
                const now = Date.now();
                const sessionAge = now - sessionData.timestamp;
                
                // Session is valid if less than 24 hours old
                if (sessionAge < 24 * 60 * 60 * 1000) {
                    console.log('Recovering existing session:', sessionData);
                    this.answers = sessionData.answers || {};
                    this.currentQuestion = sessionData.currentQuestion || 1;
                    this.timeRemaining = sessionData.timeRemaining || this.timeRemaining;
                    
                    // Show recovery notification
                    this.showRecoveryNotification();
                } else {
                    // Clear expired session
                    localStorage.removeItem(`assessment_session_${this.attemptId}`);
                }
            } catch (error) {
                console.error('Error parsing saved session:', error);
                localStorage.removeItem(`assessment_session_${this.attemptId}`);
            }
        }
    }

    // Save session to localStorage for recovery
    saveSessionToStorage() {
        try {
            const sessionData = {
                answers: this.answers,
                currentQuestion: this.currentQuestion,
                timeRemaining: this.timeRemaining,
                timestamp: Date.now()
            };
            localStorage.setItem(`assessment_session_${this.attemptId}`, JSON.stringify(sessionData));
        } catch (error) {
            console.error('Error saving session to storage:', error);
        }
    }

    // Show recovery notification
    showRecoveryNotification() {
        const notification = document.createElement('div');
        notification.className = 'alert alert-info alert-dismissible fade show position-fixed';
        notification.style.cssText = 'top: 20px; left: 20px; z-index: 9999; max-width: 400px;';
        notification.innerHTML = `
            <strong>Session Recovered!</strong> Your previous progress has been restored.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    init() {
        console.log('AssessmentPlayer init() called');
        console.log('About to setup event listeners...');
        this.setupEventListeners();
        console.log('About to generate question navigator...');
        this.generateQuestionNavigator();
        console.log('About to load first question...');
        this.loadQuestion(this.currentQuestion);
        console.log('About to start timer...');
        this.startTimer();
        console.log('About to update progress...');
        this.updateProgress();
        
        // Start periodic sync and connection monitoring
        this.startPeriodicSync();
        this.monitorConnection();
        
        console.log('AssessmentPlayer init() completed');
    }

    setupEventListeners() {
        // Navigation buttons
        document.getElementById('prev-btn').addEventListener('click', () => this.previousQuestion());
        document.getElementById('next-btn').addEventListener('click', () => this.nextQuestion());
        document.getElementById('submit-btn').addEventListener('click', () => this.showSubmitModal());
        
        // Submit confirmation
        document.getElementById('confirm-submit').addEventListener('click', () => this.submitAssessment());
        
        // Back to courses
        document.getElementById('back-to-courses').addEventListener('click', () => {
            // Use redirect URL from server if available, otherwise default to my-courses
            const redirectUrl = window.assessmentData.redirect_url || '/unlockyourskills/my-courses';
            window.location.href = redirectUrl;
        });

        // Question navigator clicks
        document.getElementById('question-grid').addEventListener('click', (e) => {
            if (e.target.classList.contains('question-number-btn')) {
                const questionNum = parseInt(e.target.textContent);
                this.loadQuestion(questionNum);
            }
        });

        // Auto-save answers when options are selected
        document.addEventListener('change', (e) => {
            if (e.target.type === 'radio') {
                this.saveAnswer(e.target.value);
            }
        });

        // Enhanced beforeunload with better user experience
        window.addEventListener('beforeunload', (e) => {
            if (this.hasUnsavedChanges()) {
                // Save session before leaving
                this.saveSessionToStorage();
                
                const message = 'You have unsaved progress. Are you sure you want to leave?';
                e.preventDefault();
                e.returnValue = message;
                return message;
            }
        });

        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Page is hidden, save session
                this.saveSessionToStorage();
            } else {
                // Page is visible again, check connection and sync
                this.monitorConnection();
            }
        });

        // Handle online/offline events
        window.addEventListener('online', () => {
            console.log('Connection restored');
            this.offlineMode = false;
            this.removeOfflineNotification();
            this.syncPendingAnswers();
        });

        window.addEventListener('offline', () => {
            console.log('Connection lost');
            this.offlineMode = true;
            this.showOfflineNotification();
        });
    }

    generateQuestionNavigator() {
        console.log('generateQuestionNavigator called');
        const grid = document.getElementById('question-grid');
        console.log('Question grid element:', grid);
        
        if (!grid) {
            console.error('Question grid element not found!');
            return;
        }
        
        grid.innerHTML = '';
        console.log('Generating navigator for', this.totalQuestions, 'questions');

        for (let i = 1; i <= this.totalQuestions; i++) {
            const btn = document.createElement('button');
            btn.className = 'question-number-btn';
            btn.textContent = i;
            btn.setAttribute('data-question', i);
            grid.appendChild(btn);
            console.log('Added question button', i);
        }
        console.log('Question navigator generated successfully');
    }

    loadQuestion(questionNumber) {
        if (questionNumber < 1 || questionNumber > this.totalQuestions) {
            return;
        }

        // Save current answer before loading new question
        if (this.currentQuestion !== questionNumber) {
            this.saveCurrentAnswer();
        }

        this.currentQuestion = questionNumber;
        this.updateQuestionDisplay();
        this.updateNavigationButtons();
        this.updateQuestionNavigator();
        this.updateProgress();
    }

    updateQuestionDisplay() {
        const container = document.getElementById('question-container');
        console.log('updateQuestionDisplay called for question:', this.currentQuestion);
        console.log('Assessment data:', window.assessmentData.assessment);
        console.log('Selected questions:', window.assessmentData.assessment.selected_questions);
        console.log('Total questions:', this.totalQuestions);
        
        const question = window.assessmentData.assessment.selected_questions[this.currentQuestion - 1];
        console.log('Current question data:', question);
        
        if (!question) {
            container.innerHTML = '<div class="alert alert-danger">Question not found</div>';
            return;
        }

        let optionsHtml = '';
        const questionType = question.type?.toLowerCase() || '';
        console.log('Question type:', question.type, 'Normalized type:', questionType);
        
        if (questionType === 'objective' && question.options) {
            console.log('Rendering objective question with', question.options.length, 'options');
            const savedAnswer = this.answers[question.id];
            console.log(`Saved answer for question ${question.id}:`, savedAnswer);
            
            optionsHtml = question.options.map(option => {
                const isSelected = savedAnswer && savedAnswer.toString() === option.id.toString();
                console.log(`Option ${option.id}: isSelected = ${isSelected}, savedAnswer = ${savedAnswer}, option.id = ${option.id}`);
                
                return `
                    <div class="option-item ${isSelected ? 'selected' : ''}" 
                         onclick="window.assessmentPlayer.selectOption('${question.id}', '${option.id}')">
                        <input type="radio" name="question_${question.id}" value="${option.id}" 
                               class="option-radio" ${isSelected ? 'checked' : ''}>
                        <span class="option-text">${this.escapeHtml(option.option_text)}</span>
                    </div>
                `;
            }).join('');
        } else if (questionType === 'subjective') {
            console.log('Rendering subjective question with textarea');
            const savedAnswer = this.answers[question.id] || '';
            const charCount = savedAnswer.length;
            optionsHtml = `
                <div class="form-group">
                    <textarea class="form-control" rows="4" placeholder="Enter your answer here..."
                              oninput="window.assessmentPlayer.updateCharCount(this, '${question.id}')"
                              onblur="window.assessmentPlayer.saveAnswer('${question.id}', this.value)">${savedAnswer}</textarea>
                    <div class="char-counter mt-2">
                        <small class="text-muted">
                            Characters: <span class="char-count">${charCount}</span>
                        </small>
                    </div>
                </div>
            `;
        } else {
            console.log('Unknown question type:', question.type, 'Defaulting to textarea');
            const savedAnswer = this.answers[question.id] || '';
            const charCount = savedAnswer.length;
            optionsHtml = `
                <div class="form-group">
                    <textarea class="form-control" rows="4" placeholder="Enter your answer here..."
                              oninput="window.assessmentPlayer.updateCharCount(this, '${question.id}')"
                              onblur="window.assessmentPlayer.saveAnswer('${question.id}', this.value)">${savedAnswer}</textarea>
                    <div class="char-counter mt-2">
                        <small class="text-muted">
                            Characters: <span class="char-count">${charCount}</span>
                        </small>
                    </div>
                </div>
            `;
        }

        const metaItems = [];
        if (question.marks) metaItems.push(`<span class="meta-item">Marks: ${question.marks}</span>`);
        if (question.difficulty_level) metaItems.push(`<span class="meta-item">Difficulty: ${question.difficulty_level}</span>`);
        if (question.skills) metaItems.push(`<span class="meta-item">Skills: ${question.skills}</span>`);

        container.innerHTML = `
            <div class="question-header fade-in">
                <div class="question-number">Question ${this.currentQuestion}</div>
                <div class="question-text">${this.escapeHtml(question.title)}</div>
                ${metaItems.length > 0 ? `<div class="question-meta">${metaItems.join('')}</div>` : ''}
            </div>
            <div class="options-container slide-in">
                ${optionsHtml}
            </div>
        `;

        // Update question counter
        document.getElementById('question-counter').textContent = `${this.currentQuestion} / ${this.totalQuestions}`;
        
        // Debug: Log the final HTML being set
        console.log('Final question HTML set:', container.innerHTML);
        console.log('Current answers object:', this.answers);
        console.log('Answer for current question:', this.answers[question.id]);
    }

    selectOption(questionId, optionId) {
        console.log(`selectOption called for question ${questionId}, option ${optionId}`);
        
        // Find all option items for this question
        const optionItems = document.querySelectorAll(`.option-item[onclick*="${questionId}"]`);
        console.log(`Found ${optionItems.length} option items for question ${questionId}`);
        
        // Remove selected class from all options
        optionItems.forEach(item => {
            item.classList.remove('selected');
            const radio = item.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = false;
            }
        });

        // Find and select the clicked option
        const clickedOption = Array.from(optionItems).find(item => {
            const radio = item.querySelector('input[type="radio"]');
            return radio && radio.value === optionId.toString();
        });
        
        if (clickedOption) {
            console.log(`Marking option ${optionId} as selected`);
            clickedOption.classList.add('selected');
            const radio = clickedOption.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
            }
        } else {
            console.warn(`Could not find option ${optionId} for question ${questionId}`);
        }

        // Save answer
        this.saveAnswer(questionId, optionId);
    }

    // Enhanced saveAnswer with offline support
    saveAnswer(questionId, answer) {
        console.log(`Saving answer for question ${questionId}:`, answer);
        console.log('Previous answers:', this.answers);
        console.log('Answer type:', typeof answer, 'Value:', answer);
        
        // Ensure answer is stored as string for consistent comparison
        this.answers[questionId] = answer.toString();
        
        console.log('Updated answers:', this.answers);
        console.log('Answer stored for question ID:', questionId, 'Value:', this.answers[questionId]);
        console.log('Answer type after storage:', typeof this.answers[questionId]);

        // Save to localStorage immediately
        this.saveSessionToStorage();

        this.updateQuestionNavigator();
        this.updateProgress();
        
        // Try to save to server (with offline handling)
        this.saveAnswerToServer(questionId, answer);
    }

    saveCurrentAnswer() {
        const currentQuestion = window.assessmentData.assessment.selected_questions[this.currentQuestion - 1];
        if (!currentQuestion) return;
        
        const questionId = currentQuestion.id;
        const textarea = document.querySelector(`textarea[name="question_${questionId}"], textarea[oninput*="${questionId}"]`);
        
        if (textarea && textarea.value.trim() !== '') {
            console.log(`Auto-saving current answer for question ${questionId}:`, textarea.value);
            this.saveAnswer(questionId, textarea.value);
        }
    }

    updateCharCount(textarea, questionId) {
        const charCount = textarea.value.length;
        const charCounter = textarea.parentNode.querySelector('.char-count');
        
        if (charCounter) {
            charCounter.textContent = charCount;
        }
        
        // Save answer after a short delay to avoid too many saves while typing
        clearTimeout(this.charCountTimeout);
        this.charCountTimeout = setTimeout(() => {
            this.saveAnswer(questionId, textarea.value);
        }, 500); // Save after 500ms of no typing
    }

    // Enhanced saveAnswerToServer with offline support and retry logic
    async saveAnswerToServer(questionId, answer) {
        try {
            const response = await fetch('/unlockyourskills/assessment-player/save-answer', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    attempt_id: this.attemptId,
                    question_id: questionId,
                    answer: answer,
                    current_question: this.currentQuestion
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            if (!result.success) {
                console.error('Failed to save answer:', result.message);
                // Store for retry
                this.pendingAnswers.set(questionId, {
                    answer: answer,
                    currentQuestion: this.currentQuestion,
                    timestamp: Date.now()
                });
            } else {
                // Remove from pending if successfully saved
                this.pendingAnswers.delete(questionId);
                this.lastServerSync = Date.now();
            }
        } catch (error) {
            console.error('Error saving answer:', error);
            this.offlineMode = true;
            
            // Store for retry when connection is restored
            this.pendingAnswers.set(questionId, {
                answer: answer,
                currentQuestion: this.currentQuestion,
                timestamp: Date.now()
            });
            
            // Show offline notification
            this.showOfflineNotification();
        }
    }

    // Show offline notification
    showOfflineNotification() {
        const existingNotification = document.querySelector('.offline-notification');
        if (existingNotification) return;

        const notification = document.createElement('div');
        notification.className = 'alert alert-warning offline-notification position-fixed';
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
        notification.innerHTML = `
            <strong>Offline Mode</strong><br>
            Your answers are being saved locally. They will sync when connection is restored.
        `;
        
        document.body.appendChild(notification);
    }

    // Remove offline notification
    removeOfflineNotification() {
        const notification = document.querySelector('.offline-notification');
        if (notification) {
            notification.remove();
        }
    }

    // Sync pending answers when connection is restored
    async syncPendingAnswers() {
        if (this.pendingAnswers.size === 0) return;

        console.log(`Syncing ${this.pendingAnswers.size} pending answers...`);
        
        for (const [questionId, data] of this.pendingAnswers) {
            try {
                await this.saveAnswerToServer(questionId, data.answer);
                // Small delay to avoid overwhelming the server
                await new Promise(resolve => setTimeout(resolve, 100));
            } catch (error) {
                console.error(`Failed to sync answer for question ${questionId}:`, error);
            }
        }
        
        if (this.pendingAnswers.size === 0) {
            this.offlineMode = false;
            this.removeOfflineNotification();
            console.log('All pending answers synced successfully');
        }
    }

    updateNavigationButtons() {
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const submitBtn = document.getElementById('submit-btn');

        prevBtn.disabled = this.currentQuestion === 1;
        nextBtn.style.display = this.currentQuestion === this.totalQuestions ? 'none' : 'inline-block';
        submitBtn.style.display = this.currentQuestion === this.totalQuestions ? 'inline-block' : 'none';
    }

    updateQuestionNavigator() {
        const buttons = document.querySelectorAll('.question-number-btn');
        buttons.forEach((btn, index) => {
            const questionNum = index + 1;
            btn.classList.remove('current', 'answered');
            
            if (questionNum === this.currentQuestion) {
                btn.classList.add('current');
            } else {
                const question = window.assessmentData.assessment.selected_questions[questionNum - 1];
                const questionId = question?.id;
                const hasAnswer = this.answers[questionId];
                
                console.log(`Question ${questionNum} (ID: ${questionId}): Has answer = ${hasAnswer ? 'YES' : 'NO'}`);
                
                if (hasAnswer) {
                    btn.classList.add('answered');
                }
            }
        });
    }

    updateProgress() {
        const answeredCount = Object.keys(this.answers).length;
        const progressPercentage = (answeredCount / this.totalQuestions) * 100;
        
        document.getElementById('progress-bar').style.width = `${progressPercentage}%`;
        
        // Update modal answered count
        const modalCount = document.getElementById('modal-answered-count');
        if (modalCount) {
            modalCount.textContent = answeredCount;
        }
    }

    previousQuestion() {
        if (this.currentQuestion > 1) {
            this.saveCurrentAnswer();
            this.loadQuestion(this.currentQuestion - 1);
        }
    }

    nextQuestion() {
        if (this.currentQuestion < this.totalQuestions) {
            this.saveCurrentAnswer();
            this.loadQuestion(this.currentQuestion + 1);
        }
    }

    showSubmitModal() {
        const answeredCount = Object.keys(this.answers).length;
        document.getElementById('modal-answered-count').textContent = answeredCount;
        
        // Store modal instance for later use
        this.submitModal = new bootstrap.Modal(document.getElementById('submitModal'));
        this.submitModal.show();
    }

    async submitAssessment() {
        if (this.isSubmitting) return;
        
        this.isSubmitting = true;
        document.getElementById('confirm-submit').disabled = true;
        document.getElementById('confirm-submit').textContent = 'Submitting...';

        // Cleanup before submitting
        this.cleanup();

        try {
            const response = await fetch('/unlockyourskills/assessment-player/submit-assessment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    attempt_id: this.attemptId
                })
            });

            const result = await response.json();
            console.log('Assessment submission response:', result);
            
            if (result.success) {
                console.log('Submission successful, calling showResults');
                this.showResults(result);
            } else {
                console.log('Submission failed:', result.message);
                alert('Failed to submit assessment: ' + result.message);
            }
        } catch (error) {
            console.error('Error submitting assessment:', error);
            alert('An error occurred while submitting the assessment. Please try again.');
        } finally {
            this.isSubmitting = false;
            document.getElementById('confirm-submit').disabled = false;
            document.getElementById('confirm-submit').textContent = 'Submit Assessment';
        }
    }

    showResults(results) {
        console.log('showResults called with:', results);
        
        // Cleanup resources
        this.cleanup();
        
        // Close submit modal safely using stored instance
        try {
            if (this.submitModal) {
                console.log('Using stored submit modal instance');
                this.submitModal.hide();
                console.log('Submit modal hidden successfully using stored instance');
            } else {
                console.log('No stored modal instance, trying to get from DOM');
                const submitModalElement = document.getElementById('submitModal');
                if (submitModalElement) {
                    const submitModal = bootstrap.Modal.getInstance(submitModalElement);
                    if (submitModal) {
                        submitModal.hide();
                        console.log('Submit modal hidden successfully from DOM instance');
                    } else {
                        console.log('No DOM instance found, creating new one to hide');
                        const newModal = new bootstrap.Modal(submitModalElement);
                        newModal.hide();
                        console.log('Submit modal hidden successfully with new instance');
                    }
                } else {
                    console.warn('Submit modal element not found in DOM');
                }
            }
        } catch (error) {
            console.warn('Could not hide submit modal:', error);
        }

        // Update results modal
        const resultScoreElement = document.getElementById('result-score');
        const resultPercentageElement = document.getElementById('result-percentage');
        const resultStatusElement = document.getElementById('result-status');
        const resultCorrectElement = document.getElementById('result-correct');

        if (resultScoreElement) resultScoreElement.textContent = `${results.score} / ${results.max_score}`;
        if (resultPercentageElement) resultPercentageElement.textContent = `${results.percentage}%`;
        if (resultStatusElement) {
            resultStatusElement.textContent = results.passed ? 'PASSED' : 'FAILED';
            resultStatusElement.className = results.passed ? 'text-success' : 'text-danger';
        }
        if (resultCorrectElement) resultCorrectElement.textContent = results.correct_answers;

        // Store redirect URL if provided by server
        console.log('Results object:', results);
        console.log('Redirect URL from server:', results.redirect_url);
        if (results.redirect_url) {
            window.assessmentData.redirect_url = results.redirect_url;
            console.log('Stored redirect URL:', window.assessmentData.redirect_url);
        }

        // Show results modal safely
        try {
            const resultsModalElement = document.getElementById('resultsModal');
            if (resultsModalElement) {
                const resultsModal = new bootstrap.Modal(resultsModalElement);
                resultsModal.show();
            }
        } catch (error) {
            console.error('Could not show results modal:', error);
            // Fallback: redirect to courses if modal fails
            if (results.redirect_url) {
                window.location.href = results.redirect_url;
            } else {
                window.location.href = '/unlockyourskills/my-courses';
            }
            return;
        }

        // Stop timer
        this.stopTimer();
    }

    startTimer() {
        this.timer = setInterval(() => {
            this.timeRemaining--;
            
            if (this.timeRemaining <= 0) {
                this.timeRemaining = 0;
                this.stopTimer();
                this.autoSubmit();
                return;
            }

            // Update display
            const timeDisplay = document.getElementById('time-remaining');
            if (timeDisplay) {
                timeDisplay.textContent = this.formatTime(this.timeRemaining);
            }

            // Update server every 30 seconds
            if (this.timeRemaining % 30 === 0) {
                this.updateTimeOnServer();
            }

            // Warning when time is running low
            if (this.timeRemaining === 300) { // 5 minutes
                this.showTimeWarning();
            }
        }, 1000);
    }

    stopTimer() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }

    formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        if (hours > 0) {
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    async updateTimeOnServer() {
        try {
            await fetch('/unlockyourskills/assessment-player/update-time', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    attempt_id: this.attemptId,
                    time_remaining: this.timeRemaining
                })
            });
        } catch (error) {
            console.error('Error updating time:', error);
        }
    }

    showTimeWarning() {
        const warning = document.createElement('div');
        warning.className = 'alert alert-warning alert-dismissible fade show position-fixed';
        warning.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
        warning.innerHTML = `
            <strong>Time Warning!</strong> You have 5 minutes remaining to complete your assessment.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(warning);
        
        // Auto-remove after 10 seconds
        setTimeout(() => {
            if (warning.parentNode) {
                warning.remove();
            }
        }, 10000);
    }

    async autoSubmit() {
        try {
            await this.submitAssessment();
        } catch (error) {
            console.error('Auto-submit failed:', error);
            // Force redirect after auto-submit failure
            setTimeout(() => {
                window.location.href = '/unlockyourskills/my-courses';
            }, 5000);
        }
    }

    hasUnsavedChanges() {
        return Object.keys(this.answers).length > 0;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Start periodic synchronization
    startPeriodicSync() {
        this.syncInterval = setInterval(() => {
            // Sync pending answers every 30 seconds
            if (this.pendingAnswers.size > 0) {
                this.syncPendingAnswers();
            }
            
            // Save session to localStorage every minute
            this.saveSessionToStorage();
        }, 30000); // 30 seconds
    }

    // Monitor internet connection
    monitorConnection() {
        // Check connection status
        const checkConnection = async () => {
            try {
                const response = await fetch('/unlockyourskills/health-check', { 
                    method: 'HEAD',
                    cache: 'no-cache'
                });
                
                if (response.ok && this.offlineMode) {
                    console.log('Connection restored, syncing pending answers...');
                    this.offlineMode = false;
                    this.removeOfflineNotification();
                    await this.syncPendingAnswers();
                }
            } catch (error) {
                // Connection is down
                if (!this.offlineMode) {
                    console.log('Connection lost, entering offline mode...');
                    this.offlineMode = true;
                }
            }
        };

        // Check every 10 seconds
        setInterval(checkConnection, 10000);
        
        // Also check when page becomes visible (user returns to tab)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                checkConnection();
            }
        });
    }

    // Cleanup method
    cleanup() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
        }
        if (this.timer) {
            this.stopTimer();
        }
        // Save final session state
        this.saveSessionToStorage();
    }
}

// Initialize assessment player when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, checking Bootstrap availability...');
    console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
    console.log('Bootstrap Modal available:', typeof bootstrap?.Modal !== 'undefined');
    
    if (window.assessmentData) {
        console.log('Assessment data found, initializing player...');
        window.assessmentPlayer = new AssessmentPlayer();
    } else {
        console.warn('Assessment data not found');
    }
});

// Handle page visibility changes to pause timer when tab is not active
document.addEventListener('visibilitychange', () => {
    if (window.assessmentPlayer) {
        if (document.hidden) {
            // Page is hidden, could implement pause functionality here
            console.log('Assessment tab is not active');
        } else {
            // Page is visible again
            console.log('Assessment tab is active again');
        }
    }
}); 