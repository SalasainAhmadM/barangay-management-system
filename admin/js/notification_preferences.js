// Edit user notification preferences
function editPreferences(userId) {
    Swal.fire({
        title: 'Loading...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    // Fetch current preferences
    fetch(`./endpoints/get_user_preferences.php?user_id=${userId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const pref = data.preferences;
                const user = data.user;

                Swal.fire({
                    title: '<i class="fas fa-cog"></i> Edit Notification Preferences',
                    html: `
                        <div class="enhanced-form">
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-user"></i>
                                    User Information
                                </div>
                                <div style="padding: 12px; background: #f3f4f6; border: 2px solid #e5e7eb; border-radius: 8px; margin-top: 8px;">
                                    <div style="font-weight: 600; color: #1f2937; margin-bottom: 4px;">
                                        ${user.first_name} ${user.middle_name ? user.middle_name + ' ' : ''}${user.last_name}
                                    </div>
                                    <div style="font-size: 13px; color: #6b7280;">
                                        <i class="fas fa-envelope" style="margin-right: 4px;"></i> ${user.email}
                                    </div>
                                    ${user.contact_number ? `
                                        <div style="font-size: 13px; color: #6b7280; margin-top: 4px;">
                                            <i class="fas fa-phone" style="margin-right: 4px;"></i> ${user.contact_number}
                                        </div>
                                    ` : ''}
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-bell"></i>
                                    Notification Preferences
                                </div>
                                
                                <div class="preference-item">
                                    <div class="preference-header">
                                        <div class="preference-label">
                                            <i class="fas fa-trash-alt" style="color: #ef4444;"></i>
                                            <span>Waste Collection Reminders</span>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="wasteReminders" ${pref.waste_reminders ? 'checked' : ''}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="preference-description">
                                        Receive notifications about upcoming waste collection schedules
                                    </div>
                                </div>

                                <div class="preference-item">
                                    <div class="preference-header">
                                        <div class="preference-label">
                                            <i class="fas fa-file-alt" style="color: #3b82f6;"></i>
                                            <span>Request Updates</span>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="requestUpdates" ${pref.request_updates ? 'checked' : ''}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="preference-description">
                                        Get notified about document request status changes
                                    </div>
                                </div>

                                <div class="preference-item">
                                    <div class="preference-header">
                                        <div class="preference-label">
                                            <i class="fas fa-bullhorn" style="color: #f59e0b;"></i>
                                            <span>Announcements</span>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="announcements" ${pref.announcements ? 'checked' : ''}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="preference-description">
                                        Receive barangay announcements and important updates
                                    </div>
                                </div>

                                <div class="preference-item">
                                    <div class="preference-header">
                                        <div class="preference-label">
                                            <i class="fas fa-sms" style="color: #10b981;"></i>
                                            <span>SMS Notifications</span>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="smsNotifications" ${pref.sms_notifications ? 'checked' : ''}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="preference-description">
                                        Enable text message notifications to mobile number
                                    </div>
                                </div>
                            </div>
                        </div>

                        <style>
                            .preference-item {
                                background: #ffffff;
                                border: 2px solid #e5e7eb;
                                border-radius: 8px;
                                padding: 16px;
                                margin-top: 12px;
                                transition: all 0.2s;
                            }
                            .preference-item:hover {
                                border-color: #6366f1;
                                box-shadow: 0 2px 8px rgba(99, 102, 241, 0.1);
                            }
                            .preference-header {
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                            }
                            .preference-label {
                                display: flex;
                                align-items: center;
                                gap: 8px;
                                font-weight: 600;
                                color: #1f2937;
                                font-size: 14px;
                            }
                            .preference-description {
                                font-size: 12px;
                                color: #6b7280;
                                margin-top: 8px;
                                padding-left: 28px;
                            }
                            .toggle-switch {
                                position: relative;
                                display: inline-block;
                                width: 48px;
                                height: 24px;
                            }
                            .toggle-switch input {
                                opacity: 0;
                                width: 0;
                                height: 0;
                            }
                            .toggle-slider {
                                position: absolute;
                                cursor: pointer;
                                top: 0;
                                left: 0;
                                right: 0;
                                bottom: 0;
                                background-color: #d1d5db;
                                transition: 0.3s;
                                border-radius: 24px;
                            }
                            .toggle-slider:before {
                                position: absolute;
                                content: "";
                                height: 18px;
                                width: 18px;
                                left: 3px;
                                bottom: 3px;
                                background-color: white;
                                transition: 0.3s;
                                border-radius: 50%;
                            }
                            input:checked + .toggle-slider {
                                background-color: #6366f1;
                            }
                            input:checked + .toggle-slider:before {
                                transform: translateX(24px);
                            }
                        </style>
                    `,
                    width: '700px',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-save"></i> Save Preferences',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#6b7280',
                    preConfirm: () => {
                        return {
                            user_id: userId,
                            waste_reminders: document.getElementById('wasteReminders').checked ? 1 : 0,
                            request_updates: document.getElementById('requestUpdates').checked ? 1 : 0,
                            announcements: document.getElementById('announcements').checked ? 1 : 0,
                            sms_notifications: document.getElementById('smsNotifications').checked ? 1 : 0
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Updating Preferences...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch('./endpoints/update_user_preferences.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(result.value)
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: 'Notification preferences have been updated successfully.',
                                        confirmButtonColor: '#6366f1'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: data.message || 'Failed to update preferences'
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
                    text: data.message || 'Failed to fetch user preferences'
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

// Export preferences data to PDF
function exportPreferences() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('landscape');

    // Title
    doc.setFontSize(16);
    doc.setFont(undefined, 'bold');
    doc.text("Notification Preferences Report", 14, 20);

    // Date and time
    doc.setFontSize(10);
    doc.setFont(undefined, 'normal');
    const now = new Date();
    doc.text(`Generated: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}`, 14, 28);

    // Collect visible rows
    const rows = [];
    document.querySelectorAll("#preferencesTable tbody tr").forEach(row => {
        if (row.style.display !== "none" && !row.querySelector('.no-data')) {
            const userName = row.cells[0]?.querySelector('.resident-name')?.textContent.trim() || "";
            const email = row.cells[0]?.querySelector('.resident-email')?.textContent.trim() || "";
            const wasteReminders = row.cells[1]?.textContent.includes('Enabled') ? 'Yes' : 'No';
            const requestUpdates = row.cells[2]?.textContent.includes('Enabled') ? 'Yes' : 'No';
            const announcements = row.cells[3]?.textContent.includes('Enabled') ? 'Yes' : 'No';
            const smsNotifications = row.cells[4]?.textContent.includes('Enabled') ? 'Yes' : 'No';
            const lastUpdated = row.cells[5]?.textContent.trim() || "";

            rows.push([userName, email, wasteReminders, requestUpdates, announcements, smsNotifications, lastUpdated]);
        }
    });

    // Check if there's data to export
    if (rows.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "No data to export",
            text: "There are no preferences to export.",
            confirmButtonColor: '#6366f1'
        });
        return;
    }

    // Summary statistics
    const stats = {
        total: rows.length,
        wasteEnabled: rows.filter(r => r[2] === 'Yes').length,
        requestEnabled: rows.filter(r => r[3] === 'Yes').length,
        announcementEnabled: rows.filter(r => r[4] === 'Yes').length,
        smsEnabled: rows.filter(r => r[5] === 'Yes').length
    };

    doc.setFontSize(9);
    doc.setTextColor(100);
    doc.text(`Total Users: ${stats.total} | Waste: ${stats.wasteEnabled} | Requests: ${stats.requestEnabled} | Announcements: ${stats.announcementEnabled} | SMS: ${stats.smsEnabled}`, 14, 34);

    // Create table
    doc.autoTable({
        head: [["User Name", "Email", "Waste\nReminders", "Request\nUpdates", "Announce-\nments", "SMS\nNotifications", "Last Updated"]],
        body: rows,
        startY: 38,
        theme: "grid",
        styles: {
            fontSize: 8,
            cellPadding: 3
        },
        headStyles: {
            fillColor: [99, 102, 241],
            textColor: 255,
            fontStyle: 'bold',
            halign: 'center'
        },
        columnStyles: {
            0: { cellWidth: 50 },   // User Name
            1: { cellWidth: 60 },   // Email
            2: { cellWidth: 25, halign: 'center' },   // Waste Reminders
            3: { cellWidth: 25, halign: 'center' },   // Request Updates
            4: { cellWidth: 25, halign: 'center' },   // Announcements
            5: { cellWidth: 25, halign: 'center' },   // SMS Notifications
            6: { cellWidth: 40 }    // Last Updated
        },
        didParseCell: function (data) {
            // Color code the Yes/No values
            if (data.column.index >= 2 && data.column.index <= 5 && data.section === 'body') {
                if (data.cell.text[0] === 'Yes') {
                    data.cell.styles.textColor = [16, 185, 129]; // Green
                    data.cell.styles.fontStyle = 'bold';
                } else if (data.cell.text[0] === 'No') {
                    data.cell.styles.textColor = [239, 68, 68]; // Red
                }
            }
        }
    });

    // Footer with statistics
    const finalY = doc.lastAutoTable.finalY || 38;
    doc.setFontSize(9);
    doc.setFont(undefined, 'bold');
    doc.text('Summary:', 14, finalY + 10);
    doc.setFont(undefined, 'normal');
    doc.text(`Total Users: ${stats.total}`, 14, finalY + 16);
    doc.text(`Waste Reminders Enabled: ${stats.wasteEnabled} (${((stats.wasteEnabled / stats.total) * 100).toFixed(1)}%)`, 14, finalY + 22);
    doc.text(`Request Updates Enabled: ${stats.requestEnabled} (${((stats.requestEnabled / stats.total) * 100).toFixed(1)}%)`, 14, finalY + 28);
    doc.text(`Announcements Enabled: ${stats.announcementEnabled} (${((stats.announcementEnabled / stats.total) * 100).toFixed(1)}%)`, 90, finalY + 16);
    doc.text(`SMS Notifications Enabled: ${stats.smsEnabled} (${((stats.smsEnabled / stats.total) * 100).toFixed(1)}%)`, 90, finalY + 22);

    // Save the PDF
    const filename = `notification_preferences_${new Date().toISOString().split('T')[0]}.pdf`;
    doc.save(filename);

    // Success notification
    Swal.fire({
        icon: 'success',
        title: 'Exported Successfully!',
        text: `${rows.length} user preference(s) exported to PDF`,
        timer: 2000,
        showConfirmButton: false
    });
}