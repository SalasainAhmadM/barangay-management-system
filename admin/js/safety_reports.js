// View Safety Report Details
function viewReport(reportId) {
    Swal.fire({
        title: 'Loading...',
        text: 'Fetching report information.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`./endpoints/get_safety_report.php?id=${reportId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const report = data.report;
                displayReportDetails(report);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Unable to fetch report details.',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Connection Error',
                text: 'Unable to connect to server.',
                confirmButtonText: 'OK'
            });
        });
}

function displayReportDetails(report) {
    const incidentTypeLabels = {
        'crime': '🚨 Crime / Suspicious Activity',
        'emergency': '🚑 Emergency',
        'infrastructure': '🏗️ Infrastructure Hazard',
        'environmental': '🌿 Environmental Concern',
        'stray_animals': '🐕 Stray Animals',
        'drugs': '💊 Drug-related Activity',
        'noise': '🔊 Noise Complaint',
        'other': '📝 Other'
    };

    // Format dates
    const incidentDate = report.incident_date ? new Date(report.incident_date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }) : 'N/A';

    const createdDate = report.created_at ? new Date(report.created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }) : 'N/A';

    const resolvedDate = report.resolved_at ? new Date(report.resolved_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }) : null;

    // Get status and urgency styles
    const getStatusStyle = (status) => {
        switch (status) {
            case 'resolved':
                return 'background: #d1fae5; color: #059669;';
            case 'closed':
                return 'background: #f3f4f6; color: #6b7280;';
            case 'in_progress':
                return 'background: #fef3c7; color: #d97706;';
            case 'under_review':
                return 'background: #dbeafe; color: #1e40af;';
            case 'pending':
                return 'background: #fef3c7; color: #d97706;';
            default:
                return 'background: #fef3c7; color: #d97706;';
        }
    };

    const getUrgencyStyle = (urgency) => {
        switch (urgency) {
            case 'emergency':
                return 'background: #fecaca; color: #991b1b;';
            case 'high':
                return 'background: #fed7aa; color: #9a3412;';
            case 'medium':
                return 'background: #fef3c7; color: #92400e;';
            case 'low':
                return 'background: #d1fae5; color: #065f46;';
            default:
                return 'background: #f3f4f6; color: #6b7280;';
        }
    };

    // Get reporter profile image
    const profileImage = report.user_image ?
        `../assets/images/user/${report.user_image}` :
        '../assets/images/user.png';

    // Images section
    let imagesHtml = '';
    if (report.images && report.images.length > 0) {
        const imageArray = JSON.parse(report.images);
        if (imageArray.length > 0) {
            imagesHtml = `
                <div class="section-header-simple" style="margin-top: 15px;">Attached Images</div>
                <div class="photo-container-simple" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; padding: 10px 0;">
                    ${imageArray.map(img => `
                        <img src="../${img}" alt="Report Image" 
                             style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb; cursor: pointer;"
                             onclick="window.open('../${img}', '_blank')">
                    `).join('')}
                </div>
            `;
        }
    }

    Swal.fire({
        title: 'Safety Report Details',
        html: `
            <div class="resident-details-container-simple" style="text-align: left;">
                <!-- Reporter Header -->
                <div class="profile-header-simple" style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding: 15px; background: #f9fafb; border-radius: 8px;">
                    ${!report.is_anonymous ? `
                        <img src="${profileImage}" alt="Profile" 
                             style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 3px solid #667eea;">
                    ` : `
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: #e5e7eb; display: flex; align-items: center; justify-content: center; border: 3px solid #667eea;">
                            <i class="fas fa-user-secret" style="font-size: 24px; color: #6b7280;"></i>
                        </div>
                    `}
                    <div style="flex: 1;">
                        <div style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 5px;">
                            ${report.is_anonymous ? 'Anonymous Reporter' : escapeHtml(report.first_name + ' ' + report.last_name)}
                        </div>
                        ${!report.is_anonymous ? `
                            <div style="font-size: 14px; color: #6b7280;">${escapeHtml(report.email || 'N/A')}</div>
                        ` : `
                            <div style="font-size: 14px; color: #6b7280;">Anonymous Report</div>
                        `}
                        <div style="margin-top: 8px; display: flex; gap: 8px;">
                            <span style="display: inline-block; padding: 6px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; ${getStatusStyle(report.status)}">
                                ${report.status.replace('_', ' ').toUpperCase()}
                            </span>
                            <span style="display: inline-block; padding: 6px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; ${getUrgencyStyle(report.urgency_level)}">
                                ${report.urgency_level.toUpperCase()}
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Report Information -->
                <div class="section-header-simple" style="font-size: 16px; font-weight: 600; color: #1f2937; margin: 20px 0 10px 0; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb;">
                    Report Information
                </div>
                <div class="resident-info-grid-simple" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <div class="info-item-simple">
                        <div class="info-label-simple" style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Report ID</div>
                        <div class="info-value-simple" style="font-size: 14px; color: #1f2937; font-weight: 500;">#${report.id}</div>
                    </div>
                    
                    <div class="info-item-simple">
                        <div class="info-label-simple" style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Incident Type</div>
                        <div class="info-value-simple" style="font-size: 14px; color: #1f2937; font-weight: 500;">${incidentTypeLabels[report.incident_type]}</div>
                    </div>
                    
                    ${!report.is_anonymous ? `
                        <div class="info-item-simple">
                            <div class="info-label-simple" style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Contact Number</div>
                            <div class="info-value-simple" style="font-size: 14px; color: #1f2937; font-weight: 500;">${escapeHtml(report.contact_number || 'N/A')}</div>
                        </div>
                    ` : ''}
                    
                    <div class="info-item-simple">
                        <div class="info-label-simple" style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Incident Date</div>
                        <div class="info-value-simple" style="font-size: 14px; color: #1f2937; font-weight: 500;">${incidentDate}</div>
                    </div>
                    
                    <div class="info-item-simple" style="grid-column: 1 / -1;">
                        <div class="info-label-simple" style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Location</div>
                        <div class="info-value-simple" style="font-size: 14px; color: #1f2937; font-weight: 500;">
                            <i class="fas fa-map-marker-alt" style="color: #ef4444;"></i> ${escapeHtml(report.location)}
                        </div>
                    </div>
                </div>
                
                <!-- Report Title & Description -->
                <div class="section-header-simple" style="font-size: 16px; font-weight: 600; color: #1f2937; margin: 20px 0 10px 0; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb;">
                    Report Details
                </div>
                <div style="margin-bottom: 15px;">
                    <div style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 8px;">
                        ${escapeHtml(report.title)}
                    </div>
                    <div style="background: #f9fafb; padding: 12px; border-radius: 8px; line-height: 1.6; color: #374151;">
                        ${escapeHtml(report.description)}
                    </div>
                </div>
                
                ${report.witness_info ? `
                    <div style="margin-bottom: 15px;">
                        <div style="font-size: 14px; font-weight: 600; color: #1f2937; margin-bottom: 8px;">
                            <i class="fas fa-user-friends"></i> Witness Information
                        </div>
                        <div style="background: #f9fafb; padding: 12px; border-radius: 8px; line-height: 1.6; color: #374151;">
                            ${escapeHtml(report.witness_info)}
                        </div>
                    </div>
                ` : ''}
                
                ${imagesHtml}
                
                ${report.response_notes ? `
                    <div style="background: #f0fdf4; border-left: 4px solid #10b981; padding: 15px; border-radius: 8px; margin-top: 15px;">
                        <div style="font-size: 14px; font-weight: 600; color: #065f46; margin-bottom: 8px;">
                            <i class="fas fa-comment-dots"></i> Official Response
                        </div>
                        <div style="line-height: 1.6; color: #065f46;">
                            ${escapeHtml(report.response_notes)}
                        </div>
                    </div>
                ` : ''}
                
                <!-- Timeline -->
                <div class="section-header-simple" style="font-size: 16px; font-weight: 600; color: #1f2937; margin: 20px 0 10px 0; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb;">
                    Timeline
                </div>
                <div class="resident-info-grid-simple" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <div class="info-item-simple">
                        <div class="info-label-simple" style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Date Reported</div>
                        <div class="info-value-simple" style="font-size: 14px; color: #1f2937; font-weight: 500;">${createdDate}</div>
                    </div>
                    
                    ${resolvedDate ? `
                        <div class="info-item-simple">
                            <div class="info-label-simple" style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Date Resolved</div>
                            <div class="info-value-simple" style="font-size: 14px; color: #1f2937; font-weight: 500;">${resolvedDate}</div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `,
        customClass: {
            popup: 'swal-view-simple'
        },
        width: '750px',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-edit"></i> Update Status',
        cancelButtonText: '<i class="fas fa-times"></i> Close',
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            updateReportStatus(report.id);
        }
    });
}

// Update Report Status
function updateReportStatus(reportId) {
    Swal.fire({
        title: 'Loading...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(`./endpoints/get_safety_report.php?id=${reportId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const report = data.report;
                Swal.fire({
                    title: '<i class="fas fa-edit"></i> Update Report Status',
                    html: `
                        <div class="enhanced-form" style="text-align: left; padding: 10px;">
                            <div class="form-section" style="margin-bottom: 20px;">
                                <div class="section-title" style="font-size: 14px; font-weight: 600; color: #1f2937; margin-bottom: 8px;">
                                    <i class="fas fa-info-circle"></i>
                                    Current Status
                                </div>
                                <div style="padding: 12px; background: #f3f4f6; border: 2px solid #e5e7eb; border-radius: 8px;">
                                    <span class="status-badge ${report.status.replace('_', '-')}" style="padding: 8px 16px; border-radius: 12px; font-size: 13px; font-weight: 600;">
                                        ${report.status.replace('_', ' ').toUpperCase()}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="form-section" style="margin-bottom: 20px;">
                                <div class="section-title" style="font-size: 14px; font-weight: 600; color: #1f2937; margin-bottom: 8px;">
                                    <i class="fas fa-sync-alt"></i>
                                    New Status <span style="color: #ef4444;">*</span>
                                </div>
                                <select class="enhanced-input" id="newStatus" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                                    <option value="pending" ${report.status === 'pending' ? 'selected' : ''}>Pending</option>
                                    <option value="under_review" ${report.status === 'under_review' ? 'selected' : ''}>Under Review</option>
                                    <option value="in_progress" ${report.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                                    <option value="resolved" ${report.status === 'resolved' ? 'selected' : ''}>Resolved</option>
                                    <option value="closed" ${report.status === 'closed' ? 'selected' : ''}>Closed</option>
                                </select>
                            </div>
                            
                            <div class="form-section">
                                <div class="section-title" style="font-size: 14px; font-weight: 600; color: #1f2937; margin-bottom: 8px;">
                                    <i class="fas fa-file-alt"></i>
                                    Response Notes
                                </div>
                                <textarea class="enhanced-textarea" id="responseNotes" rows="4" 
                                          placeholder="Add notes about the status update or response to the reporter..."
                                          style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; resize: vertical;">${report.response_notes || ''}</textarea>
                            </div>
                            
                            <div style="margin-top: 15px; padding: 10px; background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 4px;">
                                <p style="font-size: 12px; color: #1e40af; margin: 0;">
                                    <i class="fas fa-info-circle"></i> The reporter will be notified of this status update.
                                </p>
                            </div>
                        </div>
                    `,
                    width: '650px',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-save"></i> Update Status',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                    confirmButtonColor: '#667eea',
                    cancelButtonColor: '#6b7280',
                    preConfirm: () => {
                        const newStatus = document.getElementById('newStatus').value;
                        const responseNotes = document.getElementById('responseNotes').value.trim();
                        
                        if (!newStatus) {
                            Swal.showValidationMessage('Please select a status');
                            return false;
                        }
                        
                        return { id: reportId, newStatus, responseNotes };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Updating Status...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        fetch('./endpoints/update_safety_report.php', {
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
                                        text: 'Report status updated successfully!',
                                        confirmButtonColor: '#667eea'
                                    }).then(() => location.reload());
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: data.message || 'Failed to update status'
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
                    text: data.message || 'Failed to fetch report'
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

// Delete Report
function deleteReport(reportId) {
    Swal.fire({
        title: 'Delete Safety Report?',
        text: `This action cannot be undone. The report and all associated data will be permanently deleted.
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-trash"></i> Yes, Delete Report',
        cancelButtonText: '<i class="fas fa-times"></i> Cancel',
        width: '550px'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete the report.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('./endpoints/delete_safety_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: reportId })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Safety report has been deleted successfully.',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#667eea'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Could not delete report.',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Unable to delete report. Please try again.',
                        confirmButtonText: 'OK'
                    });
                });
        }
    });
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Tab switching
function switchTab(tab) {
    window.location.href = `?tab=${tab}`;
}

// Search functionality
function searchReports() {
    const input = document.getElementById('searchReportInput');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('reportsTable');
    const tr = table.getElementsByTagName('tr');
    const mobileCards = document.querySelectorAll('.mobile-cards .resident-card');

    // Filter table rows
    for (let i = 1; i < tr.length; i++) {
        const row = tr[i];
        let txtValue = '';
        const tds = row.getElementsByTagName('td');
        
        for (let j = 0; j < tds.length; j++) {
            txtValue += tds[j].textContent || tds[j].innerText;
        }
        
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }

    // Filter mobile cards
    mobileCards.forEach(card => {
        const text = card.textContent || card.innerText;
        if (text.toUpperCase().indexOf(filter) > -1) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// Export reports to PDF
function exportReports() {
    // Show loading state
    Swal.fire({
        title: 'Generating PDF...',
        text: 'Please wait while we export all safety reports',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Fetch all safety reports
    fetch('./endpoints/get_all_safety_reports.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.message || "Failed to fetch safety reports.",
                    confirmButtonColor: '#667eea'
                });
                return;
            }

            const reports = data.reports || [];

            if (reports.length === 0) {
                Swal.fire({
                    icon: "warning",
                    title: "No data to export",
                    text: "There are no safety reports in the database.",
                    confirmButtonColor: '#667eea'
                });
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Title
            doc.setFontSize(16);
            doc.setFont(undefined, 'bold');
            doc.text("Safety Reports Summary", 14, 20);

            // Date and time
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            const now = new Date();
            doc.text(`Generated: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}`, 14, 28);

            // Prepare data rows
            const headers = ["Reporter", "Incident Type", "Title", "Location", "Urgency", "Status", "Date"];
            const rows = reports.map(report => {
                const reporter = report.is_anonymous 
                    ? 'Anonymous' 
                    : `${report.first_name || ''} ${report.last_name || ''}`.trim() || 'N/A';
                
                const incidentType = (report.incident_type || 'N/A').replace('_', ' ');
                const title = (report.title || 'N/A').substring(0, 30) + 
                    (report.title && report.title.length > 30 ? '...' : '');
                const location = (report.location || 'N/A').substring(0, 35) + 
                    (report.location && report.location.length > 35 ? '...' : '');
                const urgency = (report.urgency_level || 'N/A').charAt(0).toUpperCase() + 
                    (report.urgency_level || 'N/A').slice(1);
                const status = (report.status || 'N/A').replace('_', ' ')
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');
                const date = report.created_at 
                    ? new Date(report.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    })
                    : 'N/A';

                return [reporter, incidentType, title, location, urgency, status, date];
            });

            // Create table
            doc.autoTable({
                head: [headers],
                body: rows,
                startY: 34,
                theme: "grid",
                styles: {
                    fontSize: 8,
                    cellPadding: 2
                },
                headStyles: {
                    fillColor: [102, 126, 234],
                    textColor: 255,
                    fontStyle: 'bold'
                },
                columnStyles: {
                    0: { cellWidth: 28 },  // Reporter
                    1: { cellWidth: 25 },  // Incident Type
                    2: { cellWidth: 35 },  // Title
                    3: { cellWidth: 35 },  // Location
                    4: { cellWidth: 20 },  // Urgency
                    5: { cellWidth: 25 },  // Status
                    6: { cellWidth: 22 }   // Date
                }
            });

            // Footer with statistics
            const finalY = doc.lastAutoTable.finalY || 34;
            doc.setFontSize(9);
            doc.setFont(undefined, 'bold');
            doc.text(`Total Reports: ${rows.length}`, 14, finalY + 10);

            // Add status statistics
            const statusCounts = {
                pending: reports.filter(r => r.status === 'pending').length,
                under_review: reports.filter(r => r.status === 'under_review').length,
                in_progress: reports.filter(r => r.status === 'in_progress').length,
                resolved: reports.filter(r => r.status === 'resolved').length,
                closed: reports.filter(r => r.status === 'closed').length
            };

            doc.setFont(undefined, 'normal');
            doc.setFontSize(8);
            doc.text(
                `Pending: ${statusCounts.pending} | Under Review: ${statusCounts.under_review} | In Progress: ${statusCounts.in_progress} | Resolved: ${statusCounts.resolved} | Closed: ${statusCounts.closed}`,
                14,
                finalY + 16
            );

            // Add urgency statistics
            const urgencyCounts = {
                low: reports.filter(r => r.urgency_level === 'low').length,
                medium: reports.filter(r => r.urgency_level === 'medium').length,
                high: reports.filter(r => r.urgency_level === 'high').length,
                emergency: reports.filter(r => r.urgency_level === 'emergency').length
            };

            doc.text(
                `Urgency - Low: ${urgencyCounts.low} | Medium: ${urgencyCounts.medium} | High: ${urgencyCounts.high} | Emergency: ${urgencyCounts.emergency}`,
                14,
                finalY + 22
            );

            // Save the PDF
            const filename = `safety_reports_${new Date().toISOString().split('T')[0]}.pdf`;
            doc.save(filename);

            // Success notification
            Swal.fire({
                icon: 'success',
                title: 'Exported Successfully!',
                text: `${rows.length} safety report(s) exported to PDF`,
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
                confirmButtonColor: '#667eea'
            });
        });
}