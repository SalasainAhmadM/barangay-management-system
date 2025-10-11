
function createNotification() {
    // Fetch all users for the dropdown
    fetch('./endpoints/get_all_users.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const userOptions = data.users.map(user =>
                    `<option value="${user.id}">${user.first_name} ${user.last_name} (${user.email})</option>`
                ).join('');

                Swal.fire({
                    title: '<i class="fas fa-plus-circle"></i> Create New Notification',
                    html: `
                        <div class="enhanced-form">
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-user"></i>
                                    Select User <span style="color: #ef4444;">*</span>
                                </div>
                                <select class="enhanced-input" id="userId" style="margin-top: 8px;">
                                    <option value="">Choose a user...</option>
                                    <option value="all">All Users</option>
                                    ${userOptions}
                                </select>
                            </div>
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-tag"></i>
                                    Notification Type <span style="color: #ef4444;">*</span>
                                </div>
                                <select class="enhanced-input" id="notifType" style="margin-top: 8px;">
                                    <option value="">Select type...</option>
                                    <option value="waste">Waste Collection</option>
                                    <option value="request">Document Request</option>
                                    <option value="announcement">Announcement</option>
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
                    width: '650px',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-paper-plane"></i> Send Notification',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#6b7280',
                    preConfirm: () => {
                        const userId = document.getElementById('userId').value;
                        const type = document.getElementById('notifType').value;
                        const title = document.getElementById('notifTitle').value.trim();
                        const message = document.getElementById('notifMessage').value.trim();

                        if (!userId) {
                            Swal.showValidationMessage('Please select a user');
                            return false;
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

                        return { userId, type, title, message };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Sending Notification...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        fetch('./endpoints/create_notification.php', {
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
                                        text: data.message || 'Notification sent successfully!',
                                        confirmButtonColor: '#6366f1'
                                    }).then(() => location.reload());
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

// ==================== EXPORT FUNCTION ====================

// Export notifications to PDF
function exportNotification() {
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

    // Collect visible rows
    const rows = [];
    document.querySelectorAll("#notificationsTable tbody tr").forEach(row => {
        if (!row.querySelector('.no-data')) {
            const id = row.cells[0]?.textContent.trim() || "";
            const user = row.cells[1]?.querySelector('.resident-name')?.textContent.trim() || "";
            const type = row.cells[2]?.textContent.trim() || "";
            const title = row.cells[3]?.textContent.trim() || "";
            const message = row.cells[4]?.textContent.trim().substring(0, 40) + '...' || "";
            const status = row.cells[5]?.textContent.trim() || "";
            const date = row.cells[6]?.textContent.trim() || "";

            rows.push([id, user, type, title, message, status, date]);
        }
    });

    // Check if there's data to export
    if (rows.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "No data to export",
            text: "There are no notifications to export.",
            confirmButtonColor: '#6366f1'
        });
        return;
    }

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
            2: { cellWidth: 25 },  // Type
            3: { cellWidth: 35 },  // Title
            4: { cellWidth: 40 },  // Message
            5: { cellWidth: 20 },  // Status
            6: { cellWidth: 25 }   // Date
        }
    });

    // Footer with total count
    const finalY = doc.lastAutoTable.finalY || 34;
    doc.setFontSize(9);
    doc.text(`Total Notifications: ${rows.length}`, 14, finalY + 10);

    // Save the PDF
    const filename = `notifications_${new Date().toISOString().split('T')[0]}.pdf`;
    doc.save(filename);

    // Success notification
    Swal.fire({
        icon: 'success',
        title: 'Exported Successfully!',
        text: `${rows.length} notification(s) exported to PDF`,
        timer: 2000,
        showConfirmButton: false
    });
}