/**
 * Event Registration Form - UIKit Class Enhancer
 * Automatically adds appropriate UIKit classes to form elements
 * if they are not already present
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        enhanceFormWithUIKit();
    });

    /**
     * Main function to enhance forms with UIKit classes
     */
    function enhanceFormWithUIKit() {
        // Target the event registration form
        const form = document.getElementById('event-registration-form');
        if (!form) {
            console.log('Event registration form not found');
            return;
        }

        console.log('Enhancing form with UIKit classes...');

        // Add UIKit classes to text inputs
        addUIKitClassToElements(form, 'input[type="text"]', 'uk-input');
        
        // Add UIKit classes to email inputs
        addUIKitClassToElements(form, 'input[type="email"]', 'uk-input');
        
        // Add UIKit classes to tel inputs
        addUIKitClassToElements(form, 'input[type="tel"]', 'uk-input');
        
        // Add UIKit classes to number inputs
        addUIKitClassToElements(form, 'input[type="number"]', 'uk-input');
        
        // Add UIKit classes to select elements
        addUIKitClassToElements(form, 'select', 'uk-select');
        
        // Add UIKit classes to textarea elements
        addUIKitClassToElements(form, 'textarea', 'uk-textarea');
        
        // Add UIKit classes to checkboxes
        addUIKitClassToElements(form, 'input[type="checkbox"]', 'uk-checkbox');
        
        // Add UIKit classes to radio buttons
        addUIKitClassToElements(form, 'input[type="radio"]', 'uk-radio');
        
        // Add UIKit button classes to submit button
        addUIKitClassToButton(form, '.submit-button', ['uk-button', 'uk-button-primary']);
        
        // Add UIKit classes to labels
        addUIKitClassToElements(form, 'label', 'uk-form-label');
        
        // Add UIKit classes to waiting list form if present
        const waitingListForm = document.querySelector('.waiting-list-form');
        if (waitingListForm) {
            addUIKitClassToElements(waitingListForm, 'input[type="text"]', 'uk-input');
            addUIKitClassToElements(waitingListForm, 'input[type="email"]', 'uk-input');
            addUIKitClassToElements(waitingListForm, 'input[type="number"]', 'uk-input');
            addUIKitClassToElements(waitingListForm, 'textarea', 'uk-textarea');
            addUIKitClassToElements(waitingListForm, 'label', 'uk-form-label');
            addUIKitClassToButton(waitingListForm, 'button[type="submit"]', ['uk-button', 'uk-button-primary']);
        }

        console.log('Form enhancement complete');
    }

    /**
     * Add UIKit class to elements if not already present
     * @param {Element} container - Parent element to search within
     * @param {string} selector - CSS selector for elements to enhance
     * @param {string} className - UIKit class to add
     */
    function addUIKitClassToElements(container, selector, className) {
        const elements = container.querySelectorAll(selector);
        
        elements.forEach((element) => {
            // Skip if already has the class
            if (!element.classList.contains(className)) {
                element.classList.add(className);
                console.log(`Added class "${className}" to:`, element);
            }
        });
    }

    /**
     * Add multiple UIKit classes to button elements
     * @param {Element} container - Parent element to search within
     * @param {string} selector - CSS selector for button elements
     * @param {Array<string>} classNames - Array of UIKit classes to add
     */
    function addUIKitClassToButton(container, selector, classNames) {
        const buttons = container.querySelectorAll(selector);
        
        buttons.forEach((button) => {
            classNames.forEach((className) => {
                if (!button.classList.contains(className)) {
                    button.classList.add(className);
                    console.log(`Added class "${className}" to button:`, button);
                }
            });
        });
    }

})();
