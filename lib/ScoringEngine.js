/**
 * ScoringEngine calculates performance scores and generates detailed feedback
 * Provides comprehensive assessment of student performance in OSCE cases
 */
class ScoringEngine {
    constructor() {
        this.educationalFeedback = new Map();
        this._initializeEducationalFeedback();
    }

    /**
     * Calculate comprehensive score based on performance data
     * @param {Object} performanceData - Data from PerformanceTracker
     * @param {Object} checklist - Case checklist with weights and items
     * @returns {Object} Detailed scoring results
     */
    calculateScore(performanceData, checklist) {
        if (!performanceData || !checklist) {
            throw new Error('Performance data and checklist are required for scoring');
        }

        const completionStatus = performanceData.completionStatus;
        const actionLog = performanceData.actionLog;

        const scoreResult = {
            caseId: performanceData.caseId,
            timestamp: new Date(),
            sessionDuration: completionStatus.sessionDuration,
            totalActions: actionLog.totalActions,
            
            // Overall scores
            totalScore: 0,
            maxPossibleScore: 0,
            percentageScore: 0,
            
            // Category breakdown
            categoryScores: {},
            
            // Performance metrics
            criticalItemsScore: 0,
            maxCriticalScore: 0,
            criticalPercentage: 0,
            
            // Efficiency metrics
            efficiencyBonus: 0,
            timeBonus: 0,
            
            // Detailed breakdown
            completedItems: completionStatus.completedItemsList,
            missedItems: completionStatus.missingItemsList,
            
            // Grade classification
            grade: '',
            passed: false
        };

        // Calculate scores for each category
        for (const [categoryName, category] of Object.entries(checklist)) {
            const categoryStatus = completionStatus.categories && completionStatus.categories[categoryName] 
                ? completionStatus.categories[categoryName] 
                : { items: [], completedItems: 0, totalItems: 0 };
                
            const categoryScore = this._calculateCategoryScore(
                category, 
                categoryStatus,
                actionLog
            );
            
            scoreResult.categoryScores[categoryName] = categoryScore;
            scoreResult.totalScore += categoryScore.weightedScore;
            scoreResult.maxPossibleScore += categoryScore.maxWeightedScore;
        }

        // Calculate critical items performance
        const criticalResults = this._calculateCriticalItemsScore(completionStatus);
        scoreResult.criticalItemsScore = criticalResults.score;
        scoreResult.maxCriticalScore = criticalResults.maxScore;
        scoreResult.criticalPercentage = criticalResults.percentage;

        // Calculate efficiency bonuses (only if there are items to score)
        if (scoreResult.maxPossibleScore > 0) {
            const efficiencyResults = this._calculateEfficiencyBonus(actionLog, completionStatus);
            scoreResult.efficiencyBonus = efficiencyResults.efficiencyBonus;
            scoreResult.timeBonus = efficiencyResults.timeBonus;

            // Apply bonuses to total score
            scoreResult.totalScore += scoreResult.efficiencyBonus + scoreResult.timeBonus;
        }

        // Calculate final percentage (cap at 100%)
        const rawPercentage = scoreResult.maxPossibleScore > 0 ? 
            (scoreResult.totalScore / scoreResult.maxPossibleScore) * 100 : 0;
        scoreResult.percentageScore = Math.min(100, Math.round(rawPercentage));

        // Determine grade and pass/fail
        const gradeResult = this._determineGrade(scoreResult.percentageScore, scoreResult.criticalPercentage);
        scoreResult.grade = gradeResult.grade;
        scoreResult.passed = gradeResult.passed;

        return scoreResult;
    }

    /**
     * Generate detailed feedback for student performance
     * @param {Object} performanceData - Data from PerformanceTracker
     * @param {Object} checklist - Case checklist
     * @returns {Object} Comprehensive feedback report
     */
    generateFeedback(performanceData, checklist) {
        if (!performanceData || !checklist) {
            throw new Error('Performance data and checklist are required for feedback generation');
        }

        const scoreResult = this.calculateScore(performanceData, checklist);
        const completionStatus = performanceData.completionStatus;

        const feedback = {
            caseId: performanceData.caseId,
            timestamp: new Date(),
            
            // Performance summary
            summary: this._generatePerformanceSummary(scoreResult),
            
            // Strengths and achievements
            strengths: this._identifyStrengths(scoreResult.completedItems, checklist),
            
            // Areas for improvement
            areasForImprovement: this._identifyAreasForImprovement(scoreResult.missedItems, checklist),
            
            // Educational feedback for missed items
            educationalFeedback: this._generateEducationalFeedback(scoreResult.missedItems),
            
            // Category-specific feedback
            categoryFeedback: this._generateCategoryFeedback(scoreResult.categoryScores, checklist),
            
            // Learning recommendations
            learningRecommendations: this._generateLearningRecommendations(scoreResult, checklist),
            
            // Next steps
            nextSteps: this._generateNextSteps(scoreResult)
        };

        return feedback;
    }

    /**
     * Identify missed critical items and their impact
     * @param {Object} checklist - Case checklist
     * @param {Array} missedItems - List of missed checklist items
     * @returns {Array} Critical items that were missed
     */
    identifyMissedItems(checklist, missedItems) {
        const missedCritical = [];
        const missedImportant = [];
        const missedOptional = [];

        for (const item of missedItems) {
            const itemData = {
                id: item.id,
                description: item.description,
                category: item.category,
                points: item.points,
                educationalFeedback: this.educationalFeedback.get(item.id) || null
            };

            if (item.critical) {
                missedCritical.push(itemData);
            } else if (item.points >= 4) {
                missedImportant.push(itemData);
            } else {
                missedOptional.push(itemData);
            }
        }

        return {
            critical: missedCritical,
            important: missedImportant,
            optional: missedOptional,
            totalMissed: missedItems.length
        };
    }

    /**
     * Provide educational feedback for specific missed items
     * @param {Array} missedItems - List of missed checklist items
     * @returns {Array} Educational content for missed items
     */
    provideLearningPoints(missedItems) {
        const learningPoints = [];

        for (const item of missedItems) {
            const feedback = this.educationalFeedback.get(item.id);
            if (feedback) {
                learningPoints.push({
                    itemId: item.id,
                    description: item.description,
                    category: item.category,
                    critical: item.critical,
                    learningPoint: feedback.learningPoint,
                    clinicalRationale: feedback.clinicalRationale,
                    practicalTips: feedback.practicalTips,
                    references: feedback.references || []
                });
            }
        }

        return learningPoints;
    }

    /**
     * Calculate score for a specific category
     * @param {Object} category - Category data from checklist
     * @param {Object} categoryStatus - Completion status for category
     * @param {Object} actionLog - Student action log
     * @returns {Object} Category score details
     * @private
     */
    _calculateCategoryScore(category, categoryStatus, actionLog) {
        let rawScore = 0;
        let maxRawScore = 0;
        let criticalScore = 0;
        let maxCriticalScore = 0;

        // Calculate raw scores
        for (const item of category.items || []) {
            maxRawScore += item.points;
            if (item.critical) {
                maxCriticalScore += item.points;
            }

            const itemStatus = categoryStatus.items && categoryStatus.items.find(i => i.id === item.id);
            if (itemStatus && itemStatus.completed) {
                rawScore += item.points;
                if (item.critical) {
                    criticalScore += item.points;
                }
            }
        }

        // Apply category weight
        const weight = category.weight || 0;
        const weightedScore = (rawScore / maxRawScore) * weight;
        const maxWeightedScore = weight;

        return {
            categoryName: category.name,
            rawScore,
            maxRawScore,
            weightedScore: Math.round(weightedScore * 100) / 100,
            maxWeightedScore,
            percentage: maxRawScore > 0 ? Math.round((rawScore / maxRawScore) * 100) : 0,
            criticalScore,
            maxCriticalScore,
            criticalPercentage: maxCriticalScore > 0 ? Math.round((criticalScore / maxCriticalScore) * 100) : 0,
            completedItems: categoryStatus.completedItems,
            totalItems: categoryStatus.totalItems
        };
    }

    /**
     * Calculate critical items score
     * @param {Object} completionStatus - Overall completion status
     * @returns {Object} Critical items scoring
     * @private
     */
    _calculateCriticalItemsScore(completionStatus) {
        let criticalScore = 0;
        let maxCriticalScore = 0;

        for (const item of completionStatus.completedItemsList) {
            if (item.critical) {
                criticalScore += item.points;
            }
        }

        for (const item of completionStatus.missingItemsList) {
            if (item.critical) {
                maxCriticalScore += item.points;
            }
        }

        maxCriticalScore += criticalScore;
        const percentage = maxCriticalScore > 0 ? Math.round((criticalScore / maxCriticalScore) * 100) : 0;

        return {
            score: criticalScore,
            maxScore: maxCriticalScore,
            percentage
        };
    }

    /**
     * Calculate efficiency and time bonuses
     * @param {Object} actionLog - Student action log
     * @param {Object} completionStatus - Completion status
     * @returns {Object} Bonus calculations
     * @private
     */
    _calculateEfficiencyBonus(actionLog, completionStatus) {
        let efficiencyBonus = 0;
        let timeBonus = 0;

        // Efficiency bonus: fewer actions to complete more items
        const actionsPerCompletedItem = completionStatus.completedItems > 0 ? 
            actionLog.totalActions / completionStatus.completedItems : 0;

        if (actionsPerCompletedItem > 0 && actionsPerCompletedItem < 2) {
            efficiencyBonus = 2; // Very efficient
        } else if (actionsPerCompletedItem < 3) {
            efficiencyBonus = 1; // Moderately efficient
        }

        // Time bonus: completing case quickly (under 15 minutes)
        const sessionMinutes = completionStatus.sessionDuration / (1000 * 60);
        if (sessionMinutes < 10 && completionStatus.overallCompletionRate > 80) {
            timeBonus = 3; // Very fast and thorough
        } else if (sessionMinutes < 15 && completionStatus.overallCompletionRate > 70) {
            timeBonus = 1; // Reasonably fast
        }

        return { efficiencyBonus, timeBonus };
    }

    /**
     * Determine letter grade and pass/fail status
     * @param {number} percentageScore - Overall percentage score
     * @param {number} criticalPercentage - Critical items percentage
     * @returns {Object} Grade and pass status
     * @private
     */
    _determineGrade(percentageScore, criticalPercentage) {
        let grade = 'F';
        let passed = false;

        // Must pass critical items (>= 80%) to pass overall
        if (criticalPercentage >= 80) {
            if (percentageScore >= 90) {
                grade = 'A';
                passed = true;
            } else if (percentageScore >= 80) {
                grade = 'B';
                passed = true;
            } else if (percentageScore >= 70) {
                grade = 'C';
                passed = true;
            } else if (percentageScore >= 60) {
                grade = 'D';
                passed = false; // Below passing threshold
            }
        }

        return { grade, passed };
    }

    /**
     * Generate performance summary
     * @param {Object} scoreResult - Calculated scores
     * @returns {string} Summary text
     * @private
     */
    _generatePerformanceSummary(scoreResult) {
        const passStatus = scoreResult.passed ? 'PASSED' : 'FAILED';
        const criticalStatus = scoreResult.criticalPercentage >= 80 ? 'adequate' : 'inadequate';
        
        return `Performance Summary: ${passStatus} (${scoreResult.grade}) - ${scoreResult.percentageScore}% overall score. ` +
               `Critical items performance: ${criticalStatus} (${scoreResult.criticalPercentage}%). ` +
               `Completed ${scoreResult.completedItems.length} of ${scoreResult.completedItems.length + scoreResult.missedItems.length} checklist items.`;
    }

    /**
     * Identify student strengths
     * @param {Array} completedItems - Items completed by student
     * @param {Object} checklist - Case checklist
     * @returns {Array} List of strengths
     * @private
     */
    _identifyStrengths(completedItems, checklist) {
        const strengths = [];
        const categoryStrengths = {};

        // Group completed items by category
        for (const item of completedItems) {
            if (!categoryStrengths[item.category]) {
                categoryStrengths[item.category] = [];
            }
            categoryStrengths[item.category].push(item);
        }

        // Identify category-level strengths
        for (const [categoryName, items] of Object.entries(categoryStrengths)) {
            const category = checklist[categoryName];
            const completionRate = items.length / category.items.length;
            
            if (completionRate >= 0.8) {
                strengths.push(`Excellent performance in ${categoryName} (${Math.round(completionRate * 100)}% completion)`);
            } else if (completionRate >= 0.6) {
                strengths.push(`Good performance in ${categoryName} (${Math.round(completionRate * 100)}% completion)`);
            }
        }

        // Identify specific critical items completed
        const criticalCompleted = completedItems.filter(item => item.critical);
        if (criticalCompleted.length > 0) {
            strengths.push(`Successfully completed ${criticalCompleted.length} critical assessment items`);
        }

        return strengths;
    }

    /**
     * Identify areas for improvement
     * @param {Array} missedItems - Items missed by student
     * @param {Object} checklist - Case checklist
     * @returns {Array} List of improvement areas
     * @private
     */
    _identifyAreasForImprovement(missedItems, checklist) {
        const improvements = [];
        const categoryMissed = {};

        // Group missed items by category
        for (const item of missedItems) {
            if (!categoryMissed[item.category]) {
                categoryMissed[item.category] = [];
            }
            categoryMissed[item.category].push(item);
        }

        // Identify category-level weaknesses
        for (const [categoryName, items] of Object.entries(categoryMissed)) {
            const category = checklist[categoryName];
            const missedRate = items.length / category.items.length;
            
            if (missedRate >= 0.5) {
                improvements.push(`Significant gaps in ${categoryName} - missed ${items.length} of ${category.items.length} items`);
            } else if (missedRate >= 0.3) {
                improvements.push(`Room for improvement in ${categoryName} - missed ${items.length} items`);
            }
        }

        // Highlight missed critical items
        const criticalMissed = missedItems.filter(item => item.critical);
        if (criticalMissed.length > 0) {
            improvements.push(`CRITICAL: Missed ${criticalMissed.length} essential assessment items`);
        }

        return improvements;
    }

    /**
     * Generate educational feedback for missed items
     * @param {Array} missedItems - Items missed by student
     * @returns {Array} Educational feedback
     * @private
     */
    _generateEducationalFeedback(missedItems) {
        const feedback = [];

        for (const item of missedItems) {
            const educationalContent = this.educationalFeedback.get(item.id);
            if (educationalContent) {
                feedback.push({
                    itemId: item.id,
                    description: item.description,
                    category: item.category,
                    critical: item.critical,
                    ...educationalContent
                });
            }
        }

        return feedback;
    }

    /**
     * Generate category-specific feedback
     * @param {Object} categoryScores - Scores by category
     * @param {Object} checklist - Case checklist
     * @returns {Object} Category feedback
     * @private
     */
    _generateCategoryFeedback(categoryScores, checklist) {
        const feedback = {};

        for (const [categoryName, score] of Object.entries(categoryScores)) {
            let categoryFeedback = '';
            
            if (score.percentage >= 90) {
                categoryFeedback = `Excellent work in ${categoryName}. You demonstrated comprehensive understanding.`;
            } else if (score.percentage >= 70) {
                categoryFeedback = `Good performance in ${categoryName}. Minor areas for refinement.`;
            } else if (score.percentage >= 50) {
                categoryFeedback = `Adequate performance in ${categoryName}. Several important areas missed.`;
            } else {
                categoryFeedback = `Significant improvement needed in ${categoryName}. Review fundamental concepts.`;
            }

            feedback[categoryName] = {
                score: score.percentage,
                feedback: categoryFeedback,
                completedItems: score.completedItems,
                totalItems: score.totalItems,
                criticalPercentage: score.criticalPercentage
            };
        }

        return feedback;
    }

    /**
     * Generate learning recommendations
     * @param {Object} scoreResult - Score results
     * @param {Object} checklist - Case checklist
     * @returns {Array} Learning recommendations
     * @private
     */
    _generateLearningRecommendations(scoreResult, checklist) {
        const recommendations = [];

        // Overall performance recommendations
        if (scoreResult.percentageScore < 70) {
            recommendations.push('Review fundamental clinical assessment skills and systematic approach to patient evaluation');
        }

        if (scoreResult.criticalPercentage < 80) {
            recommendations.push('Focus on essential diagnostic and management skills - practice identifying critical clinical actions');
        }

        // Category-specific recommendations
        for (const [categoryName, score] of Object.entries(scoreResult.categoryScores)) {
            if (score.percentage < 60) {
                switch (categoryName) {
                    case 'historyTaking':
                        recommendations.push('Practice systematic history taking - use structured approaches like OPQRST for symptom analysis');
                        break;
                    case 'physicalExamination':
                        recommendations.push('Review physical examination techniques and practice systematic examination sequences');
                        break;
                    case 'investigations':
                        recommendations.push('Study appropriate diagnostic test selection and interpretation for common presentations');
                        break;
                    case 'diagnosis':
                        recommendations.push('Practice clinical reasoning and differential diagnosis development');
                        break;
                    case 'management':
                        recommendations.push('Review emergency management protocols and treatment guidelines');
                        break;
                }
            }
        }

        return recommendations;
    }

    /**
     * Generate next steps for student
     * @param {Object} scoreResult - Score results
     * @returns {Array} Next steps
     * @private
     */
    _generateNextSteps(scoreResult) {
        const nextSteps = [];

        if (scoreResult.passed) {
            nextSteps.push('Congratulations on passing! Continue practicing to maintain and improve your skills.');
            nextSteps.push('Consider attempting more complex cases to challenge yourself further.');
        } else {
            nextSteps.push('Review the missed items and educational feedback provided.');
            nextSteps.push('Practice the specific skills identified in the improvement areas.');
            nextSteps.push('Retake this case after additional study and practice.');
        }

        nextSteps.push('Discuss your performance with a clinical supervisor or mentor.');
        nextSteps.push('Use the learning recommendations to guide your continued education.');

        return nextSteps;
    }

    /**
     * Initialize educational feedback database
     * @private
     */
    _initializeEducationalFeedback() {
        // History taking feedback
        this.educationalFeedback.set('onset_timing', {
            learningPoint: 'Timing and onset of symptoms are crucial for diagnosis',
            clinicalRationale: 'Acute onset chest pain (<6 hours) suggests acute coronary syndrome and requires immediate evaluation',
            practicalTips: 'Always ask: "When did this start?" and "How did it come on - suddenly or gradually?"'
        });

        this.educationalFeedback.set('pain_character', {
            learningPoint: 'Characterizing chest pain helps differentiate cardiac from non-cardiac causes',
            clinicalRationale: 'Crushing, pressure-like pain with radiation suggests myocardial ischemia',
            practicalTips: 'Use OPQRST: Onset, Provocation, Quality, Radiation, Severity, Timing'
        });

        this.educationalFeedback.set('associated_symptoms', {
            learningPoint: 'Associated symptoms provide important diagnostic clues',
            clinicalRationale: 'Nausea, sweating, and dyspnea are common in acute MI and increase diagnostic probability',
            practicalTips: 'Ask specifically about shortness of breath, nausea, sweating, and arm/jaw pain'
        });

        this.educationalFeedback.set('past_medical_history', {
            learningPoint: 'Past medical history identifies risk factors and guides management',
            clinicalRationale: 'Diabetes and hypertension are major cardiovascular risk factors',
            practicalTips: 'Always ask about diabetes, hypertension, previous heart problems, and family history'
        });

        this.educationalFeedback.set('medications', {
            learningPoint: 'Current medications affect diagnosis and treatment decisions',
            clinicalRationale: 'Antihypertensive medications may mask symptoms; diabetes medications affect glucose management',
            practicalTips: 'Ask about all medications including over-the-counter and herbal supplements'
        });

        this.educationalFeedback.set('risk_factors', {
            learningPoint: 'Cardiovascular risk factors are essential for risk stratification',
            clinicalRationale: 'Smoking history, family history, and metabolic factors guide probability assessment',
            practicalTips: 'Assess smoking, family history, diabetes, hypertension, and cholesterol levels'
        });

        // Physical examination feedback
        this.educationalFeedback.set('vital_signs', {
            learningPoint: 'Vital signs provide immediate assessment of hemodynamic stability',
            clinicalRationale: 'Hypertension and tachycardia may indicate sympathetic response to pain/stress',
            practicalTips: 'Always check BP, HR, RR, temperature, and oxygen saturation in chest pain patients'
        });

        this.educationalFeedback.set('cardiovascular_exam', {
            learningPoint: 'Cardiovascular examination can reveal complications of MI',
            clinicalRationale: 'New murmurs, gallops, or irregular rhythms may indicate mechanical complications',
            practicalTips: 'Listen for murmurs, gallops, and irregular rhythms; assess for signs of heart failure'
        });

        this.educationalFeedback.set('respiratory_exam', {
            learningPoint: 'Respiratory examination assesses for heart failure and other complications',
            clinicalRationale: 'Crackles may indicate acute heart failure secondary to MI',
            practicalTips: 'Listen for crackles, wheezes, and assess work of breathing'
        });

        // Investigation feedback
        this.educationalFeedback.set('ecg', {
            learningPoint: 'ECG is the most important initial test in suspected ACS',
            clinicalRationale: 'ST elevation indicates acute vessel occlusion requiring immediate reperfusion',
            practicalTips: 'Obtain ECG within 10 minutes of presentation; look for ST changes and Q waves'
        });

        this.educationalFeedback.set('cardiac_enzymes', {
            learningPoint: 'Cardiac biomarkers confirm myocardial necrosis',
            clinicalRationale: 'Elevated troponin indicates myocardial injury and helps with diagnosis and prognosis',
            practicalTips: 'Order troponin levels; may be normal early in presentation'
        });

        this.educationalFeedback.set('basic_labs', {
            learningPoint: 'Basic labs assess for complications and guide treatment',
            clinicalRationale: 'Glucose levels important in diabetics; electrolytes affect cardiac rhythm',
            practicalTips: 'Order CBC, BMP, and consider PT/PTT if anticoagulation planned'
        });

        this.educationalFeedback.set('chest_xray', {
            learningPoint: 'Chest X-ray assesses for heart failure and other complications',
            clinicalRationale: 'Pulmonary edema may indicate acute heart failure from MI',
            practicalTips: 'Look for pulmonary edema, cardiomegaly, and rule out other causes of chest pain'
        });

        // Diagnosis feedback
        this.educationalFeedback.set('primary_diagnosis', {
            learningPoint: 'Accurate diagnosis is essential for appropriate treatment',
            clinicalRationale: 'STEMI requires immediate reperfusion therapy - time is myocardium',
            practicalTips: 'Consider ACS in any chest pain patient; use clinical presentation, ECG, and biomarkers'
        });

        this.educationalFeedback.set('differential', {
            learningPoint: 'Consider differential diagnoses to avoid missing other serious conditions',
            clinicalRationale: 'Aortic dissection, PE, and pneumothorax can mimic MI and require different treatments',
            practicalTips: 'Always consider: aortic dissection, pulmonary embolism, pneumothorax, and esophageal causes'
        });

        // Management feedback
        this.educationalFeedback.set('emergency_treatment', {
            learningPoint: 'Immediate treatment can be life-saving in STEMI',
            clinicalRationale: 'Aspirin, oxygen (if hypoxic), and pain relief are immediate priorities',
            practicalTips: 'Give aspirin 325mg, oxygen if SpO2 <90%, morphine for pain, consider nitroglycerin'
        });

        this.educationalFeedback.set('cardiology_consult', {
            learningPoint: 'STEMI requires immediate cardiology consultation for reperfusion',
            clinicalRationale: 'Primary PCI is preferred reperfusion strategy when available within 90 minutes',
            practicalTips: 'Call cardiology immediately for STEMI; discuss reperfusion options (PCI vs thrombolytics)'
        });
    }
}

export default ScoringEngine;