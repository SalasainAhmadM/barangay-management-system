
// Create notification with multi-user selection
function createNotification() {
    // Fetch all users for the dropdown
    fetch('./endpoints/get_all_users.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const userOptions = data.users.map(user => {
                    const fullName = `${user.first_name} ${user.last_name}`;
                    return `<option value="${user.id}" 
                            data-name="${fullName}" 
                            data-contact="${user.contact_number || ''}"
                            data-street="${user.street_name || ''}"
                            data-waste="${user.preferences.waste_reminders}"
                            data-request="${user.preferences.request_updates}"
                            data-announcement="${user.preferences.announcements}"
                            data-sms="${user.preferences.sms_notifications}">
                            ${fullName} (${user.email})
                        </option>`;
                }).join('');

                // Get unique streets from all users
                const uniqueStreets = [...new Set(
                    data.users
                        .map(user => user.street_name)
                        .filter(street => street && street.trim() !== '')
                )].sort();

                const streetOptions = uniqueStreets.map(street =>
                    `<option value="${street}">${street}</option>`
                ).join('');

                Swal.fire({
                    title: '<i class="fas fa-plus-circle"></i> Create New Notification',
                    html: `
                        <div class="enhanced-form">
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-user"></i>
                                    Select Recipients <span style="color: #ef4444;">*</span>
                                </div>
                                
                                <!-- Recipient Type Selection -->
                                <div style="margin-top: 10px; display: flex; gap: 10px;">
                                    <label style="flex: 1; cursor: pointer;">
                                        <input type="radio" name="recipientType" value="single" checked 
                                               style="margin-right: 5px;">
                                        Single User
                                    </label>
                                    <label style="flex: 1; cursor: pointer;">
                                        <input type="radio" name="recipientType" value="multiple" 
                                               style="margin-right: 5px;">
                                        Multiple Users
                                    </label>
                                    <label style="flex: 1; cursor: pointer;">
                                        <input type="radio" name="recipientType" value="all" 
                                               style="margin-right: 5px;">
                                        All Users
                                    </label>
                                </div>
                                
                                <!-- Single User Select (default) -->
                                <select class="enhanced-input" id="userId" style="margin-top: 8px;">
                                    <option value="">Choose a user...</option>
                                    ${userOptions}
                                </select>
                                
                                <!-- Multiple Users Select (hidden by default) -->
                                <div id="multipleUsersSection" style="display: none; margin-top: 8px;">
                                    <select class="enhanced-input" id="userIds" multiple size="8" 
                                            style="height: 200px;">
                                        ${userOptions}
                                    </select>
                                    <div style="font-size: 12px; color: #6b7280; margin-top: 6px;">
                                        <i class="fas fa-info-circle"></i> 
                                        Hold Ctrl to select multiple users. 
                                        <span id="selectedCount" style="font-weight: 600; color: #6366f1;">0 selected</span>
                                    </div>
                                </div>
                                
                                <!-- Selected Users Display -->
                                <div id="selectedUsersDisplay" style="display: none; margin-top: 10px;">
                                    <div style="font-size: 12px; color: #374151; font-weight: 600; margin-bottom: 6px;">
                                        Selected Users:
                                    </div>
                                    <div id="selectedUsersList" style="max-height: 120px; overflow-y: auto; 
                                         border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px; 
                                         background: #f9fafb;">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Street Filter (hidden by default) -->
                            <div class="form-section" id="streetFilterSection" style="display: none;">
                                <div class="section-title">
                                    <i class="fas fa-road"></i>
                                    Filter by Street <span style="color: #6b7280;">(Optional)</span>
                                </div>
                                <select class="enhanced-input" id="streetFilter" style="margin-top: 8px;">
                                    <option value="all">All Streets</option>
                                    ${streetOptions}
                                </select>
                                <div id="streetInfo" style="font-size: 12px; color: #6b7280; margin-top: 6px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-info-circle"></i>
                                    <span id="streetUserCount">Select a street to see user count</span>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-tag"></i>
                                    Notification Type <span style="color: #ef4444;">*</span>
                                </div>
                                <select class="enhanced-input" id="notifType" style="margin-top: 8px;">
                                    <option value="">Select type...</option>
                                    <option value="waste" data-pref="waste_reminders">Waste Collection</option>
                                    <option value="request" data-pref="request_updates">Document Request</option>
                                    <option value="announcement" data-pref="announcements">Announcement</option>
                                    <option value="alert">Alert</option>
                                    <option value="success">Success</option>
                                </select>
                            </div>
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-heading"></i>
                                    Title <span style="color: #ef4444;">*</span>
                                </div>
                                <input type="text" class="enhanced-input" id="notifTitle" 
                                       placeholder="Enter notification title" style="margin-top: 8px;">
                            </div>
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-comment-alt"></i>
                                    Message <span style="color: #ef4444;">*</span>
                                </div>
                                <textarea class="enhanced-textarea" id="notifMessage" rows="4" 
                                          placeholder="Enter notification message..." style="margin-top: 8px;"></textarea>
                            </div>
                        </div>
                    `,
                    width: '700px',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-paper-plane"></i> Send Notification',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#6b7280',
                    didOpen: () => {
                        const recipientTypeRadios = document.querySelectorAll('input[name="recipientType"]');
                        const singleUserSelect = document.getElementById('userId');
                        const multipleUsersSection = document.getElementById('multipleUsersSection');
                        const multipleUsersSelect = document.getElementById('userIds');
                        const selectedUsersDisplay = document.getElementById('selectedUsersDisplay');
                        const selectedUsersList = document.getElementById('selectedUsersList');
                        const selectedCount = document.getElementById('selectedCount');
                        const typeSelect = document.getElementById('notifType');
                        const streetFilterSection = document.getElementById('streetFilterSection');
                        const streetFilter = document.getElementById('streetFilter');
                        const streetUserCount = document.getElementById('streetUserCount');

                        // Function to update selected users display
                        function updateSelectedUsersDisplay() {
                            const selectedOptions = Array.from(multipleUsersSelect.selectedOptions);
                            selectedCount.textContent = `${selectedOptions.length} selected`;
                            
                            if (selectedOptions.length > 0) {
                                selectedUsersDisplay.style.display = 'block';
                                selectedUsersList.innerHTML = selectedOptions.map(option => {
                                    const userName = option.dataset.name;
                                    return `
                                        <div style="display: flex; align-items: center; gap: 8px; padding: 4px 8px; 
                                                    background: white; border-radius: 4px; margin-bottom: 4px;">
                                            <i class="fas fa-user" style="color: #6366f1; font-size: 12px;"></i>
                                            <span style="font-size: 13px; color: #374151;">${userName}</span>
                                        </div>
                                    `;
                                }).join('');
                            } else {
                                selectedUsersDisplay.style.display = 'none';
                            }
                        }

                        // Function to filter notification types based on preferences
                        function filterNotificationTypes(selectedUsers) {
                            if (selectedUsers.length === 0) {
                                // Enable all types if no users selected
                                Array.from(typeSelect.options).forEach(option => {
                                    if (option.value) option.disabled = false;
                                });
                                return;
                            }

                            // Check which notification types ALL selected users support
                            const allSupportWaste = selectedUsers.every(user => 
                                user.dataset.waste === 'true'
                            );
                            const allSupportRequest = selectedUsers.every(user => 
                                user.dataset.request === 'true'
                            );
                            const allSupportAnnouncement = selectedUsers.every(user => 
                                user.dataset.announcement === 'true'
                            );

                            Array.from(typeSelect.options).forEach(option => {
                                const prefType = option.dataset.pref;
                                if (prefType === 'waste_reminders' && !allSupportWaste) {
                                    option.disabled = true;
                                } else if (prefType === 'request_updates' && !allSupportRequest) {
                                    option.disabled = true;
                                } else if (prefType === 'announcements' && !allSupportAnnouncement) {
                                    option.disabled = true;
                                } else if (option.value) {
                                    option.disabled = false;
                                }
                            });

                            // Reset selection if currently selected type is now disabled
                            if (typeSelect.value && typeSelect.options[typeSelect.selectedIndex].disabled) {
                                typeSelect.value = '';
                            }
                        }

                        // Function to count users by street and preferences
                        function updateStreetUserCount() {
                            const selectedStreet = streetFilter.value;
                            const selectedType = typeSelect.value;

                            if (!selectedType) {
                                streetUserCount.textContent = 'Select notification type first';
                                return;
                            }

                            let filteredUsers = data.users;

                            // Filter by street
                            if (selectedStreet !== 'all') {
                                filteredUsers = filteredUsers.filter(user =>
                                    user.street_name === selectedStreet
                                );
                            }

                            // Filter by notification preference
                            const prefMap = {
                                'waste': 'waste_reminders',
                                'request': 'request_updates',
                                'announcement': 'announcements'
                            };

                            const prefKey = prefMap[selectedType];
                            if (prefKey) {
                                filteredUsers = filteredUsers.filter(user =>
                                    user.preferences[prefKey] === true
                                );
                            }

                            const count = filteredUsers.length;
                            const streetText = selectedStreet === 'all' ? 'all streets' : selectedStreet;
                            streetUserCount.innerHTML = `<strong>${count}</strong> user(s) in <strong>${streetText}</strong> will receive this notification`;

                            if (count === 0) {
                                streetUserCount.style.color = '#ef4444';
                            } else {
                                streetUserCount.style.color = '#10b981';
                            }
                        }

                        // Handle recipient type change
                        recipientTypeRadios.forEach(radio => {
                            radio.addEventListener('change', function() {
                                const recipientType = this.value;
                                
                                // Hide all sections first
                                singleUserSelect.style.display = 'none';
                                multipleUsersSection.style.display = 'none';
                                streetFilterSection.style.display = 'none';
                                selectedUsersDisplay.style.display = 'none';
                                
                                if (recipientType === 'single') {
                                    singleUserSelect.style.display = 'block';
                                    singleUserSelect.value = '';
                                    
                                    // Enable all types for single user selection
                                    Array.from(typeSelect.options).forEach(option => {
                                        if (option.value) option.disabled = false;
                                    });
                                } else if (recipientType === 'multiple') {
                                    multipleUsersSection.style.display = 'block';
                                    multipleUsersSelect.selectedIndex = -1;
                                    updateSelectedUsersDisplay();
                                } else if (recipientType === 'all') {
                                    streetFilterSection.style.display = 'block';
                                    updateStreetUserCount();
                                    
                                    // Enable all types for "All Users"
                                    Array.from(typeSelect.options).forEach(option => {
                                        if (option.value) option.disabled = false;
                                    });
                                }
                            });
                        });

                        // Handle single user selection
                        singleUserSelect.addEventListener('change', function() {
                            const selectedOption = this.options[this.selectedIndex];
                            
                            if (this.value) {
                                const wasteEnabled = selectedOption.dataset.waste === 'true';
                                const requestEnabled = selectedOption.dataset.request === 'true';
                                const announcementEnabled = selectedOption.dataset.announcement === 'true';

                                Array.from(typeSelect.options).forEach(option => {
                                    const prefType = option.dataset.pref;
                                    if (prefType === 'waste_reminders' && !wasteEnabled) {
                                        option.disabled = true;
                                    } else if (prefType === 'request_updates' && !requestEnabled) {
                                        option.disabled = true;
                                    } else if (prefType === 'announcements' && !announcementEnabled) {
                                        option.disabled = true;
                                    } else if (option.value) {
                                        option.disabled = false;
                                    }
                                });

                                if (typeSelect.value && typeSelect.options[typeSelect.selectedIndex].disabled) {
                                    typeSelect.value = '';
                                }
                            }
                        });

                        // Handle multiple users selection
                        multipleUsersSelect.addEventListener('change', function() {
                            updateSelectedUsersDisplay();
                            const selectedOptions = Array.from(this.selectedOptions);
                            filterNotificationTypes(selectedOptions);
                        });

                        // Update count when street filter or type changes (for "All Users")
                        streetFilter.addEventListener('change', updateStreetUserCount);
                        typeSelect.addEventListener('change', () => {
                            const recipientType = document.querySelector('input[name="recipientType"]:checked').value;
                            if (recipientType === 'all') {
                                updateStreetUserCount();
                            }
                        });
                    },
                    preConfirm: () => {
                        const recipientType = document.querySelector('input[name="recipientType"]:checked').value;
                        const type = document.getElementById('notifType').value;
                        const title = document.getElementById('notifTitle').value.trim();
                        const message = document.getElementById('notifMessage').value.trim();
                        
                        let userIds = [];
                        let streetFilter = null;
                        
                        if (recipientType === 'single') {
                            const userId = document.getElementById('userId').value;
                            if (!userId) {
                                Swal.showValidationMessage('Please select a user');
                                return false;
                            }
                            userIds = [userId];
                        } else if (recipientType === 'multiple') {
                            const selectedOptions = Array.from(document.getElementById('userIds').selectedOptions);
                            if (selectedOptions.length === 0) {
                                Swal.showValidationMessage('Please select at least one user');
                                return false;
                            }
                            userIds = selectedOptions.map(option => option.value);
                        } else if (recipientType === 'all') {
                            userIds = ['all'];
                            streetFilter = document.getElementById('streetFilter').value;
                        }
                        
                        if (!type) {
                            Swal.showValidationMessage('Please select notification type');
                            return false;
                        }
                        if (!title) {
                            Swal.showValidationMessage('Please enter a title');
                            return false;
                        }
                        if (!message) {
                            Swal.showValidationMessage('Please enter a message');
                            return false;
                        }

                        return {
                            userIds,
                            recipientType,
                            type,
                            title,
                            message,
                            streetFilter,
                            allUsers: data.users
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        showSMSConfirmation(result.value);
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to load users'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Connection Error!',
                text: 'Unable to connect to server'
            });
        });
}

// Show SMS confirmation dialog (updated for multi-user support)
function showSMSConfirmation(notificationData) {
    const { userIds, recipientType, type, title, message, streetFilter, allUsers } = notificationData;
    const defaultSMSMessage = `Hello! You have a new ${type} notification:\n${title}\n${message.substring(0, 100)}${message.length > 100 ? '...' : ''}`;

    let eligibleSMSCount = 0;
    let recipientDescription = '';
    let eligibleUsers = [];

    if (recipientType === 'all') {
        eligibleUsers = allUsers.filter(user =>
            user.preferences.sms_notifications &&
            user.contact_number &&
            user.contact_number.trim() !== ''
        );

        if (streetFilter && streetFilter !== 'all') {
            eligibleUsers = eligibleUsers.filter(user => user.street_name === streetFilter);
            recipientDescription = `in ${streetFilter}`;
        } else {
            recipientDescription = 'from all streets';
        }
        eligibleSMSCount = eligibleUsers.length;
    } else if (recipientType === 'multiple') {
        eligibleUsers = allUsers.filter(user => 
            userIds.includes(user.id.toString()) &&
            user.preferences.sms_notifications &&
            user.contact_number &&
            user.contact_number.trim() !== ''
        );
        eligibleSMSCount = eligibleUsers.length;
        recipientDescription = `${userIds.length} selected user(s)`;
    } else {
        // Single user
        const user = allUsers.find(u => u.id.toString() === userIds[0]);
        if (user && user.preferences.sms_notifications && user.contact_number) {
            eligibleUsers = [user];
            eligibleSMSCount = 1;
            recipientDescription = `${user.first_name} ${user.last_name}`;
        }
    }

    const canSendSMS = eligibleSMSCount > 0;

    let smsWarningMessage = '';
    if (!canSendSMS) {
        if (recipientType === 'all' && eligibleSMSCount === 0) {
            smsWarningMessage = `<i class="fas fa-exclamation-triangle"></i> No users ${recipientDescription} have SMS notifications enabled with valid contact numbers`;
        } else if (recipientType === 'multiple') {
            smsWarningMessage = `<i class="fas fa-exclamation-triangle"></i> None of the selected users have SMS notifications enabled with valid contact numbers`;
        } else {
            smsWarningMessage = '<i class="fas fa-exclamation-triangle"></i> User has disabled SMS notifications or has no valid contact number';
        }
    }

    Swal.fire({
        title: '<i class="fas fa-check-circle"></i> Confirm Notification',
        html: `
            <div class="enhanced-form" style="text-align: left;">
                <div style="background: #ecfdf5; border: 2px solid #10b981; border-radius: 10px; padding: 16px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <i class="fas fa-info-circle" style="color: #10b981; font-size: 20px;"></i>
                        <strong style="color: #059669;">Notification Ready to Send</strong>
                    </div>
                    <p style="margin: 0; color: #047857; font-size: 14px;">
                        ${recipientType === 'all' 
                            ? `This notification will be sent to users ${recipientDescription} with enabled preferences`
                            : `This notification will be sent to ${recipientDescription}`
                        }
                    </p>
                </div>
                
                <div class="form-section">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                        <input type="checkbox" id="sendSMSCheckbox" style="width: 18px; height: 18px; cursor: pointer;" ${!canSendSMS ? 'disabled' : ''}>
                        <label for="sendSMSCheckbox" style="cursor: pointer; font-weight: 600; color: #374151; margin: 0;">
                            <i class="fas fa-sms"></i> Send SMS notification ${eligibleSMSCount > 0 ? `(${eligibleSMSCount} recipients)` : ''}
                        </label>
                    </div>
                    
                    ${!canSendSMS ?
                        `<div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 6px; padding: 10px; margin-top: 10px;">
                            <small style="color: #92400e;">
                                ${smsWarningMessage}
                            </small>
                        </div>`
                        : ''
                    }
                    
                    <div id="smsMessageSection" style="display: none; margin-top: 15px;">
                        ${eligibleUsers.length > 0 && eligibleUsers.length <= 5 ?
                            `<div style="background: #f9fafb; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                                <div style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">
                                    <i class="fas fa-users"></i> Recipients:
                                </div>
                                ${eligibleUsers.map(user => `
                                    <div style="font-size: 12px; color: #374151; padding: 4px 0;">
                                        <i class="fas fa-user" style="color: #6366f1; margin-right: 6px;"></i>
                                        <strong>${user.first_name} ${user.last_name}</strong> - ${user.contact_number}
                                    </div>
                                `).join('')}
                            </div>`
                            :
                            `<div style="background: #f9fafb; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                                <div style="font-size: 12px; color: #6b7280;">
                                    <i class="fas fa-users"></i> Recipients: <strong>${eligibleSMSCount} users</strong> will receive SMS
                                </div>
                            </div>`
                        }
                        
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
                            <i class="fas fa-comment-alt"></i> SMS Message:
                        </label>
                        <textarea id="smsMessage" class="enhanced-textarea" rows="5" 
                                  style="resize: vertical;">${defaultSMSMessage}</textarea>
                        <div style="font-size: 12px; color: #6b7280; margin-top: 6px;">
                            <i class="fas fa-info-circle"></i> Character count: <span id="charCount">${defaultSMSMessage.length}</span>/160
                        </div>
                    </div>
                </div>
            </div>
        `,
        width: '600px',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-paper-plane"></i> Confirm',
        cancelButtonText: '<i class="fas fa-times"></i> Cancel',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        didOpen: () => {
            const checkbox = document.getElementById('sendSMSCheckbox');
            const messageSection = document.getElementById('smsMessageSection');
            const messageTextarea = document.getElementById('smsMessage');
            const charCount = document.getElementById('charCount');
            const confirmButton = Swal.getConfirmButton();

            if (checkbox) {
                checkbox.addEventListener('change', function () {
                    if (this.checked) {
                        messageSection.style.display = 'block';
                        confirmButton.innerHTML = '<i class="fas fa-paper-plane"></i> Confirm & Send';
                    } else {
                        messageSection.style.display = 'none';
                        confirmButton.innerHTML = '<i class="fas fa-paper-plane"></i> Confirm';
                    }
                });
            }

            if (messageTextarea) {
                messageTextarea.addEventListener('input', function () {
                    charCount.textContent = this.value.length;
                    if (this.value.length > 160) {
                        charCount.style.color = '#ef4444';
                    } else {
                        charCount.style.color = '#6b7280';
                    }
                });
            }
        },
        preConfirm: () => {
            const sendSMS = document.getElementById('sendSMSCheckbox').checked;
            const smsMessage = document.getElementById('smsMessage')?.value || '';

            if (sendSMS && !smsMessage.trim()) {
                Swal.showValidationMessage('Please enter an SMS message');
                return false;
            }

            return {
                ...notificationData,
                send_sms: sendSMS,
                sms_message: smsMessage,
                eligibleUsers
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            processNotificationSend(result.value);
        }
    });
}

// Process the notification send
function processNotificationSend(notificationData) {
    Swal.fire({
        title: 'Sending Notification...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    // Send notification
    fetch('./endpoints/create_notification.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            userIds: notificationData.userIds,
            recipientType: notificationData.recipientType,
            type: notificationData.type,
            title: notificationData.title,
            message: notificationData.message,
            streetFilter: notificationData.streetFilter
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // If SMS should be sent
                if (notificationData.send_sms && notificationData.eligibleUsers.length > 0) {
                    const phoneNumbers = notificationData.eligibleUsers.map(user => user.contact_number);
                    
                    if (phoneNumbers.length === 1) {
                        // Send single SMS
                        sendSMS(phoneNumbers[0], notificationData.sms_message)
                            .then(() => {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    html: `
                                        <p>${data.message || 'Notification sent successfully!'}</p>
                                        <p style="color: #10b981; font-weight: 600;">
                                            <i class="fas fa-check-circle"></i> SMS notification sent!
                                        </p>
                                    `,
                                    confirmButtonColor: '#6366f1'
                                }).then(() => location.reload());
                            })
                            .catch(() => {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Partial Success',
                                    html: `
                                        <p>${data.message || 'Notification sent successfully!'}</p>
                                        <p style="color: #f59e0b;">
                                            <i class="fas fa-exclamation-triangle"></i> SMS failed to send
                                        </p>
                                    `,
                                    confirmButtonColor: '#6366f1'
                                }).then(() => location.reload());
                            });
                    } else {
                        // Send bulk SMS
                        sendBulkSMS(phoneNumbers, notificationData.sms_message, data.message);
                    }
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message || 'Notification sent successfully!',
                        confirmButtonColor: '#6366f1'
                    }).then(() => location.reload());
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to send notification'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Connection Error!',
                text: 'Unable to connect to server'
            });
        });
}

// Send SMS functions
function sendSMS(phoneNumber, message) {
    return fetch('./endpoints/send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            number: phoneNumber,
            message: message
        })
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Failed to send SMS');
            }
            return data;
        });
}

function sendBulkSMS(phoneNumbers, message, notificationMessage) {
    fetch('./endpoints/send_bulk.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            numbers: phoneNumbers,
            message: message
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    html: `
                        <p>${notificationMessage || 'Notification sent successfully!'}</p>
                        <p style="color: #10b981; font-weight: 600;">
                            <i class="fas fa-check-circle"></i> Bulk SMS sent to ${data.sent_count} recipient(s)!
                        </p>
                        ${data.invalid_count > 0 ?
                            `<p style="color: #f59e0b; font-size: 14px;">
                                <i class="fas fa-exclamation-triangle"></i> ${data.invalid_count} invalid numbers skipped
                            </p>`
                            : ''
                        }
                    `,
                    confirmButtonColor: '#6366f1'
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Partial Success',
                    html: `
                        <p>${notificationMessage || 'Notification sent successfully!'}</p>
                        <p style="color: #f59e0b;">
                            <i class="fas fa-exclamation-triangle"></i> Bulk SMS failed to send: ${data.message}
                        </p>
                    `,
                    confirmButtonColor: '#6366f1'
                }).then(() => location.reload());
            }
        })
        .catch(error => {
            console.error('Bulk SMS Error:', error);
            Swal.fire({
                icon: 'warning',
                title: 'Partial Success',
                html: `
                    <p>${notificationMessage || 'Notification sent successfully!'}</p>
                    <p style="color: #f59e0b;">
                        <i class="fas fa-exclamation-triangle"></i> Bulk SMS failed to send
                    </p>
                `,
                confirmButtonColor: '#6366f1'
            }).then(() => location.reload());
        });
}
// Edit notification
function editNotification(id) {
    Swal.fire({
        title: 'Loading...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(`./endpoints/get_notification.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const notif = data.notification;

                Swal.fire({
                    title: '<i class="fas fa-edit"></i> Edit Notification',
                    html: `
                        <div class="enhanced-form">
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-user"></i>
                                    User
                                </div>
                                <input type="text" class="enhanced-input" 
                                       value="${notif.first_name} ${notif.last_name} (${notif.email})" 
                                       readonly style="margin-top: 8px; background-color: #f3f4f6; cursor: not-allowed;">
                            </div>
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-info-circle"></i>
                                    Current Status
                                </div>
                                <div style="padding: 12px; background: #f3f4f6; border: 2px solid #e5e7eb; border-radius: 8px; margin-top: 8px;">
                                    <span class="status-badge ${notif.type}">
                                        <i class="fas ${notif.icon}"></i>
                                        ${notif.type.charAt(0).toUpperCase() + notif.type.slice(1)}
                                    </span>
                                    <span class="status-badge ${notif.is_read ? 'read' : 'unread'}" style="margin-left: 8px;">
                                        ${notif.is_read ? 'Read' : 'Unread'}
                                    </span>
                                </div>
                            </div>
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-tag"></i>
                                    Notification Type <span style="color: #ef4444;">*</span>
                                </div>
                                <select class="enhanced-input" id="editNotifType" style="margin-top: 8px;">
                                    <option value="waste" ${notif.type === 'waste' ? 'selected' : ''}>Waste Collection</option>
                                    <option value="request" ${notif.type === 'request' ? 'selected' : ''}>Document Request</option>
                                    <option value="announcement" ${notif.type === 'announcement' ? 'selected' : ''}>Announcement</option>
                                    <option value="alert" ${notif.type === 'alert' ? 'selected' : ''}>Alert</option>
                                    <option value="success" ${notif.type === 'success' ? 'selected' : ''}>Success</option>
                                </select>
                            </div>
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-heading"></i>
                                    Title <span style="color: #ef4444;">*</span>
                                </div>
                                <input type="text" class="enhanced-input" id="editNotifTitle" 
                                       value="${notif.title}" style="margin-top: 8px;">
                            </div>
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-comment-alt"></i>
                                    Message <span style="color: #ef4444;">*</span>
                                </div>
                                <textarea class="enhanced-textarea" id="editNotifMessage" rows="4" 
                                          style="margin-top: 8px;">${notif.message}</textarea>
                            </div>
                        </div>
                    `,
                    width: '650px',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-save"></i> Update Notification',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#6b7280',
                    preConfirm: () => {
                        const type = document.getElementById('editNotifType').value;
                        const title = document.getElementById('editNotifTitle').value.trim();
                        const message = document.getElementById('editNotifMessage').value.trim();

                        if (!title) {
                            Swal.showValidationMessage('Please enter a title');
                            return false;
                        }
                        if (!message) {
                            Swal.showValidationMessage('Please enter a message');
                            return false;
                        }

                        return { id, type, title, message };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Updating Notification...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        fetch('./endpoints/update_notification.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(result.value)
                        })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: 'Notification updated successfully!',
                                        confirmButtonColor: '#6366f1'
                                    }).then(() => location.reload());
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: data.message || 'Failed to update notification'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Connection Error!',
                                    text: 'Unable to connect to server'
                                });
                            });
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to fetch notification'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Connection Error!',
                text: 'Unable to connect to server'
            });
        });
}

// Delete notification
function deleteNotification(id) {
    Swal.fire({
        title: 'Delete Notification?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-trash"></i> Yes, delete it!',
        cancelButtonText: '<i class="fas fa-times"></i> Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleting...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('./endpoints/delete_notification.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Notification has been deleted.',
                            confirmButtonColor: '#6366f1'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message || 'Failed to delete notification'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error!',
                        text: 'Unable to connect to server'
                    });
                });
        }
    });
}

// Export notifications to PDF
function exportNotification() {
    // Show loading state
    Swal.fire({
        title: 'Generating PDF...',
        text: 'Please wait while we export all notifications',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Fetch all notifications data
    fetch('./endpoints/get_all_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success || data.notifications.length === 0) {
                Swal.fire({
                    icon: "warning",
                    title: "No data to export",
                    text: "There are no notifications in the database.",
                    confirmButtonColor: '#6366f1'
                });
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Title
            doc.setFontSize(16);
            doc.setFont(undefined, 'bold');
            doc.text("Notifications Report", 14, 20);

            // Date and time
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            const now = new Date();
            doc.text(`Generated: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}`, 14, 28);

            // Prepare rows from fetched data
            const rows = data.notifications.map(notif => {
                const id = `#${notif.id}`;
                const fullName = `${notif.first_name || ''} ${notif.middle_name || ''} ${notif.last_name || ''}`.trim();
                const type = notif.type.charAt(0).toUpperCase() + notif.type.slice(1);
                const title = notif.title || 'N/A';
                const message = (notif.message || '').substring(0, 50) + (notif.message.length > 50 ? '...' : '');
                const status = notif.is_read ? 'Read' : 'Unread';
                const createdDate = new Date(notif.created_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                return [id, fullName, type, title, message, status, createdDate];
            });

            // Create table
            doc.autoTable({
                head: [["ID", "User", "Type", "Title", "Message", "Status", "Date"]],
                body: rows,
                startY: 34,
                theme: "grid",
                styles: {
                    fontSize: 8,
                    cellPadding: 2
                },
                headStyles: {
                    fillColor: [99, 102, 241],
                    textColor: 255,
                    fontStyle: 'bold'
                },
                columnStyles: {
                    0: { cellWidth: 15 },  // ID
                    1: { cellWidth: 30 },  // User
                    2: { cellWidth: 22 },  // Type
                    3: { cellWidth: 35 },  // Title
                    4: { cellWidth: 40 },  // Message
                    5: { cellWidth: 18 },  // Status
                    6: { cellWidth: 30 }   // Date
                }
            });

            // Footer with total count and statistics
            const finalY = doc.lastAutoTable.finalY || 34;
            doc.setFontSize(9);
            doc.setFont(undefined, 'bold');
            doc.text(`Total Notifications: ${rows.length}`, 14, finalY + 10);

            // Add statistics
            const readCount = data.notifications.filter(n => n.is_read).length;
            const unreadCount = data.notifications.filter(n => !n.is_read).length;

            doc.setFont(undefined, 'normal');
            doc.text(`Read: ${readCount} | Unread: ${unreadCount}`, 14, finalY + 16);

            // Save the PDF
            const filename = `notifications_report_${new Date().toISOString().split('T')[0]}.pdf`;
            doc.save(filename);

            // Success notification
            Swal.fire({
                icon: 'success',
                title: 'Exported Successfully!',
                text: `${rows.length} notification(s) exported to PDF`,
                timer: 2000,
                showConfirmButton: false
            });
        })
        .catch(error => {
            console.error('Export error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Export Failed',
                text: 'There was an error exporting the data. Please try again.',
                confirmButtonColor: '#6366f1'
            });
        });
}