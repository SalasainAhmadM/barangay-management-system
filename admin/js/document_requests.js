// Global variable to track current filter
let currentStatusFilter = 'all';

// Initialize status filter listeners on page load
document.addEventListener('DOMContentLoaded', function () {
    initializeStatusFilters();
});

// Initialize status card click handlers
function initializeStatusFilters() {
    const statCards = document.querySelectorAll('.stat-card');

    statCards.forEach(card => {
        card.addEventListener('click', function () {
            const cardClasses = this.className.split(' ');
            const statusClass = cardClasses.find(cls =>
                ['total', 'pending', 'processing', 'approved', 'ready', 'completed', 'rejected', 'cancelled'].includes(cls)
            );

            if (statusClass) {
                filterRequestsByStatus(statusClass, this);
            }
        });
    });
}

// Filter requests by status
function filterRequestsByStatus(status, clickedCard) {
    const rows = document.querySelectorAll("#requestsTable tbody tr");
    const mobileCards = document.querySelectorAll(".mobile-cards .resident-card");
    const allStatCards = document.querySelectorAll('.stat-card');

    // Remove active class from all cards
    allStatCards.forEach(card => card.classList.remove('active'));

    // If clicking the same filter, reset to show all
    if (currentStatusFilter === status) {
        currentStatusFilter = 'all';
        showAllRequests(rows, mobileCards);
        return;
    }

    // Set new filter and add active class
    currentStatusFilter = status;
    clickedCard.classList.add('active');

    // Show all if "total" is clicked
    if (status === 'total') {
        showAllRequests(rows, mobileCards);
        return;
    }

    // Filter desktop table rows
    let visibleCount = 0;
    rows.forEach(row => {
        if (row.querySelector('.no-data')) return;

        const statusBadge = row.querySelector('td:nth-child(7) .status-badge');
        if (statusBadge) {
            const rowStatus = statusBadge.textContent.toLowerCase().trim().replace(/ /g, '_');
            const matches = rowStatus === status;
            row.style.display = matches ? "" : "none";
            if (matches) visibleCount++;
        }
    });

    // Filter mobile cards
    mobileCards.forEach(card => {
        const statusSpan = card.querySelector('.card-status');
        if (statusSpan) {
            const cardStatus = statusSpan.textContent.toLowerCase().trim().replace(/ /g, '_');
            const matches = cardStatus === status;
            card.style.display = matches ? "" : "none";
        }
    });

    // Handle no results
    handleFilterResults(visibleCount, status);

    // Hide pagination when filtering
    const pagination = document.querySelector(".pagination-container");
    if (pagination) {
        pagination.style.display = status === 'total' ? "flex" : "none";
    }
}

// Show all requests (reset filter)
function showAllRequests(rows, mobileCards) {
    rows.forEach(row => {
        if (!row.querySelector('.no-data')) {
            row.style.display = "";
        }
    });

    mobileCards.forEach(card => {
        card.style.display = "";
    });

    // Remove no data message if exists
    const noDataMsg = document.getElementById('noDataFilterMsg');
    if (noDataMsg) {
        noDataMsg.remove();
    }

    // Show pagination
    const pagination = document.querySelector(".pagination-container");
    if (pagination) {
        pagination.style.display = "flex";
    }
}

// Handle filter results display
function handleFilterResults(visibleCount, status) {
    let noDataMsg = document.getElementById('noDataFilterMsg');

    if (visibleCount === 0) {
        if (!noDataMsg) {
            noDataMsg = document.createElement("tr");
            noDataMsg.id = 'noDataFilterMsg';
            const tbody = document.querySelector("#requestsTable tbody");
            if (tbody) {
                tbody.appendChild(noDataMsg);
            }
        }
        noDataMsg.innerHTML = `
            <td colspan="9" class="no-data">
                <i class="fas fa-filter"></i>
                <p>No requests found with status: <strong>${status.replace(/_/g, ' ')}</strong></p>
            </td>
        `;
        noDataMsg.style.display = "";
    } else {
        if (noDataMsg) {
            noDataMsg.style.display = "none";
        }
    }
}

// Enhanced search requests to work with status filter
function searchRequests() {
    const input = document.getElementById('searchInput');
    const filter = input ? input.value.toUpperCase() : '';
    const rows = document.querySelectorAll("#requestsTable tbody tr");

    rows.forEach(row => {
        if (row.querySelector('.no-data') || row.id === 'noDataFilterMsg') return;

        const text = row.textContent || row.innerText;

        // Check if matches search filter
        const matchesSearch = !filter || text.toUpperCase().indexOf(filter) > -1;

        // Check if matches status filter
        let matchesStatusFilter = true;
        if (currentStatusFilter !== 'all' && currentStatusFilter !== 'total') {
            const statusBadge = row.querySelector('td:nth-child(7) .status-badge');
            if (statusBadge) {
                const rowStatus = statusBadge.textContent.toLowerCase().trim().replace(/ /g, '_');
                matchesStatusFilter = rowStatus === currentStatusFilter;
            }
        }

        // Show row only if matches both filters
        row.style.display = (matchesSearch && matchesStatusFilter) ? "" : "none";
    });

    // Handle mobile cards search
    const mobileCards = document.querySelectorAll(".mobile-cards .resident-card");
    mobileCards.forEach(card => {
        const text = card.textContent || card.innerText;
        const matchesSearch = !filter || text.toUpperCase().indexOf(filter) > -1;

        let matchesStatusFilter = true;
        if (currentStatusFilter !== 'all' && currentStatusFilter !== 'total') {
            const statusSpan = card.querySelector('.card-status');
            if (statusSpan) {
                const cardStatus = statusSpan.textContent.toLowerCase().trim().replace(/ /g, '_');
                matchesStatusFilter = cardStatus === currentStatusFilter;
            }
        }

        card.style.display = (matchesSearch && matchesStatusFilter) ? "" : "none";
    });
}

// Update the generic searchData function to use searchRequests
function searchData() {
    searchRequests();
}

// Reset filters when switching tabs
function switchTab(tab) {
    // Reset status filter
    currentStatusFilter = 'all';
    const allStatCards = document.querySelectorAll('.stat-card');
    allStatCards.forEach(card => card.classList.remove('active'));

    // Navigate to tab
    window.location.href = `?tab=${tab}`;
}

// View request details in SweetAlert modal with file handling
function viewRequestDetails(requestId) {
    Swal.fire({
        title: 'Loading...',
        text: 'Fetching request details.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`./endpoints/get_request_details.php?id=${requestId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const request = data.request;

                // Format dates
                const submittedDate = request.submitted_date ? new Date(request.submitted_date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }) : 'N/A';

                const approvedDate = request.approved_date ? new Date(request.approved_date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }) : 'N/A';

                const releasedDate = request.released_date ? new Date(request.released_date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }) : 'N/A';

                const expectedDate = request.expected_date ? new Date(request.expected_date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                }) : 'N/A';

                // Get user profile image path
                const profileImage = request.user_image ?
                    `../assets/images/user/${request.user_image}` :
                    '../assets/images/user.png';

                // Format status with color
                const getStatusStyle = (status) => {
                    switch (status) {
                        case 'completed':
                            return 'background: #e9ecef; color: #6c757d;';
                        case 'approved':
                            return 'background: #d4edda; color: #28a745;';
                        case 'ready':
                            return 'background: #c3e6cb; color: #20c997;';
                        case 'processing':
                            return 'background: #d1ecf1; color: #17a2b8;';
                        case 'pending':
                            return 'background: #fff3cd; color: #ffc107;';
                        case 'rejected':
                            return 'background: #f8d7da; color: #dc3545;';
                        case 'cancelled':
                            return 'background: #f8d7da; color: #dc3545;';
                        default:
                            return 'background: #fff3cd; color: #ffc107;';
                    }
                };

                // Document type badge
                const typeStyle = request.document_type === 'certificate' ?
                    'background: #ddd6fe; color: #7c3aed;' :
                    'background: #fef3c7; color: #d97706;';

                // Generate uploaded files HTML
                let filesHTML = '';
                if (request.uploaded_files && request.uploaded_files.length > 0) {
                    filesHTML = `
                        <div class="section-header-simple">Uploaded Documents</div>
                        <div class="uploaded-files-grid">
                    `;

                    request.uploaded_files.forEach(file => {
                        const fileExt = file.file_type.toLowerCase();
                        const isPDF = fileExt === 'pdf';
                        const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt);

                        let fileIcon = 'fa-file';
                        let iconColor = '#6c757d';

                        if (isPDF) {
                            fileIcon = 'fa-file-pdf';
                            iconColor = '#dc3545';
                        } else if (isImage) {
                            fileIcon = 'fa-file-image';
                            iconColor = '#28a745';
                        }

                        const uploadDate = new Date(file.uploaded_at).toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric'
                        });

                        filesHTML += `
                            <div class="file-item" onclick="handleFileClick('${file.file_path}', '${fileExt}', '${file.file_name}')">
                                <div class="file-icon">
                                    <i class="fas ${fileIcon}" style="color: ${iconColor};"></i>
                                </div>
                                <div class="file-info">
                                    <div class="file-name">${file.file_name}</div>
                                    <div class="file-meta">${uploadDate} • ${fileExt.toUpperCase()}</div>
                                </div>
                                <div class="file-action">
                                    <i class="fas ${isImage ? 'fa-eye' : 'fa-download'}"></i>
                                </div>
                            </div>
                        `;
                    });

                    filesHTML += '</div>';
                }

                Swal.fire({
                    title: 'Document Request Details',
                    html: `
                    <div class="resident-details-container-simple">
                        <!-- Requester Header with Profile Image -->
                        <div class="profile-header-simple">
                            <img src="${profileImage}" alt="Profile" class="resident-profile-image-simple">
                            <div class="profile-info-simple">
                                <div class="resident-name-simple">
                                    ${request.first_name} ${request.middle_name || ''} ${request.last_name}
                                </div>
                                <div class="resident-email-simple">${request.email || 'N/A'}</div>
                                <div style="display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-top: 8px; ${getStatusStyle(request.status)}">
                                    ${request.status.toUpperCase().replace('_', ' ')}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Request Information Section -->
                        <div class="section-header-simple">Request Information</div>
                        <div class="resident-info-grid-simple">
                            <div class="info-item-simple">
                                <div class="info-label-simple">Request ID</div>
                                <div class="info-value-simple">${request.request_id}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Document Type</div>
                                <div class="info-value-simple">
                                    <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; ${typeStyle}">
                                        ${request.document_type.toUpperCase()}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Document Name</div>
                                <div class="info-value-simple">
                                    <i class="fas ${request.icon}"></i> ${request.document_name}
                                </div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Purpose</div>
                                <div class="info-value-simple">${request.purpose || 'N/A'}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Fee</div>
                                <div class="info-value-simple">
                                    ${request.fee == 0 ? '<span style="color: #28a745; font-weight: 600;">Free</span>' : '₱' + parseFloat(request.fee).toFixed(2)}
                                </div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Processing Time</div>
                                <div class="info-value-simple">${request.processing_days || 'N/A'}</div>
                            </div>
                            
                            ${request.additional_info ? `
                            <div class="info-item-simple full-width">
                                <div class="info-label-simple">Additional Information</div>
                                <div class="info-value-simple">${request.additional_info}</div>
                            </div>
                            ` : ''}
                        </div>
                        
                        ${filesHTML}
                        
                        <!-- Requester Details Section -->
                        <div class="section-header-simple">Requester Information</div>
                        <div class="resident-info-grid-simple">
                            <div class="info-item-simple">
                                <div class="info-label-simple">First Name</div>
                                <div class="info-value-simple">${request.first_name || 'N/A'}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Middle Name</div>
                                <div class="info-value-simple">${request.middle_name || 'N/A'}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Last Name</div>
                                <div class="info-value-simple">${request.last_name || 'N/A'}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Email Address</div>
                                <div class="info-value-simple">${request.email || 'N/A'}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Contact Number</div>
                                <div class="info-value-simple">${request.contact_number || 'N/A'}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Address</div>
                                <div class="info-value-simple">${request.address || 'N/A'}</div>
                            </div>
                        </div>
                        
                        <!-- Timeline Section -->
                        <div class="section-header-simple">Request Timeline</div>
                        <div class="resident-info-grid-simple">
                            <div class="info-item-simple">
                                <div class="info-label-simple">Date Submitted</div>
                                <div class="info-value-simple">${submittedDate}</div>
                            </div>
                            
                            ${request.expected_date && ['pending', 'processing'].includes(request.status) ? `
                            <div class="info-item-simple">
                                <div class="info-label-simple">Expected Completion</div>
                                <div class="info-value-simple">${expectedDate}</div>
                            </div>
                            ` : ''}
                            
                            ${request.approved_date ? `
                            <div class="info-item-simple">
                                <div class="info-label-simple">Date Approved</div>
                                <div class="info-value-simple">${approvedDate}</div>
                            </div>
                            ` : ''}
                            
                            ${request.released_date ? `
                            <div class="info-item-simple">
                                <div class="info-label-simple">Date Released</div>
                                <div class="info-value-simple">${releasedDate}</div>
                            </div>
                            ` : ''}
                            
                            ${request.rejection_reason && request.status === 'rejected' ? `
                            <div class="info-item-simple full-width">
                                <div class="info-label-simple">Rejection Reason</div>
                                <div class="info-value-simple" style="color: #dc3545; font-weight: 500;">
                                    ${request.rejection_reason}
                                </div>
                            </div>
                            ` : ''}
                            
                            ${request.notes ? `
                            <div class="info-item-simple full-width">
                                <div class="info-label-simple">Admin Notes</div>
                                <div class="info-value-simple">${request.notes}</div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `,
                    customClass: {
                        popup: 'swal-view-simple'
                    },
                    confirmButtonText: 'Close',
                    confirmButtonColor: '#00247c',
                    width: '700px'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Unable to fetch request details.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#00247c'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Connection Error',
                text: 'Unable to connect to server.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#00247c'
            });
        });
}

// Handle file click - view image or download PDF
function handleFileClick(filePath, fileType, fileName) {
    const fileExt = fileType.toLowerCase();
    const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt);

    if (isImage) {
        // Show image in modal
        Swal.fire({
            title: fileName,
            html: `
                <div style="max-width: 100%; max-height: 70vh; overflow: auto;">
                    <img src="${filePath}" alt="${fileName}" style="max-width: 100%; height: auto; border-radius: 8px;">
                </div>
            `,
            confirmButtonText: 'Close',
            confirmButtonColor: '#00247c',
            width: '800px',
            showCloseButton: true
        });
    } else {
        // Download PDF
        const link = document.createElement('a');
        link.href = filePath;
        link.download = fileName;
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

{/* <div class="info-item-simple">
    <div class="info-label-simple">Payment Status</div>
    <div class="info-value-simple">
        <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; ${request.payment_status === 'paid' ? 'background: #d4edda; color: #28a745;' : 'background: #fff3cd; color: #ffc107;'}">
            ${request.payment_status.toUpperCase()}
        </span>
    </div>
</div> */}

// Update request status
function updateRequestStatus(requestId) {
    Swal.fire({
        title: 'Loading...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    // First fetch current status
    fetch(`./endpoints/get_request_details.php?id=${requestId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const request = data.request;

                Swal.fire({
                    title: '<i class="fas fa-edit"></i> Update Request Status',
                    html: `
                        <div class="enhanced-form">
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-hashtag"></i>
                                    Request ID
                                </div>
                                <input type="text" class="enhanced-input" value="${request.request_id}" readonly 
                                       style="background-color: #f3f4f6; margin-top: 8px; cursor: not-allowed;">
                            </div>
                            
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-info-circle"></i>
                                    Current Status
                                </div>
                                <div style="padding: 12px; background: #f3f4f6; border: 2px solid #e5e7eb; border-radius: 8px; margin-top: 8px;">
                                    <span class="status-badge ${request.status}">${request.status.replace('_', ' ').toUpperCase()}</span>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-sync-alt"></i>
                                    New Status <span style="color: #ef4444;">*</span>
                                </div>
                                <select id="newStatus" class="enhanced-input" style="margin-top: 8px;">
                                    <option value="">Select Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="approved">Approved</option>
                                    <option value="ready">Ready for Pickup</option>
                                    <option value="completed">Completed</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            
                            <div id="rejectionReasonDiv" class="form-section" style="display: none;">
                                <div class="section-title">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Rejection Reason <span style="color: #ef4444;">*</span>
                                </div>
                                <input type="text" id="rejectionReason" class="enhanced-input" 
                                       placeholder="Enter rejection reason" style="margin-top: 8px;">
                            </div>
                            
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-file-alt"></i>
                                    Admin Notes
                                </div>
                                <textarea class="enhanced-textarea" id="adminNotes" rows="4" 
                                          placeholder="Add any additional notes or comments..."></textarea>
                            </div>
                        </div>
                    `,
                    width: '650px',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-save"></i> Update Status',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#6b7280',
                    didOpen: () => {
                        // Show/hide rejection reason based on status
                        document.getElementById('newStatus').addEventListener('change', function () {
                            const rejectionDiv = document.getElementById('rejectionReasonDiv');
                            if (this.value === 'rejected') {
                                rejectionDiv.style.display = 'block';
                            } else {
                                rejectionDiv.style.display = 'none';
                            }
                        });
                    },
                    preConfirm: () => {
                        const newStatus = document.getElementById('newStatus').value;
                        const rejectionReason = document.getElementById('rejectionReason').value;
                        const adminNotes = document.getElementById('adminNotes').value;

                        if (!newStatus) {
                            Swal.showValidationMessage('Please select a status');
                            return false;
                        }

                        if (newStatus === 'rejected' && !rejectionReason) {
                            Swal.showValidationMessage('Please provide a rejection reason');
                            return false;
                        }

                        return {
                            request_id: requestId,
                            status: newStatus,
                            rejection_reason: rejectionReason,
                            notes: adminNotes
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Updating Status...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch('./endpoints/update_request_status.php', {
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
                                        text: 'Request status has been updated successfully.',
                                        confirmButtonColor: '#6366f1'
                                    }).then(() => {
                                        location.reload();
                                    });
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
                    text: data.message || 'Failed to fetch request details'
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
// Delete request
function deleteRequest(requestId) {
    Swal.fire({
        title: 'Delete Request?',
        text: "This action cannot be undone. Are you sure you want to delete this request?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('./endpoints/delete_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ request_id: requestId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Request has been deleted successfully.',
                            confirmButtonColor: '#00247c'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to delete request',
                            confirmButtonColor: '#00247c'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while deleting the request',
                        confirmButtonColor: '#00247c'
                    });
                });
        }
    });
}

// Export requests data to PDF
function exportRequests() {
    // Show loading state
    Swal.fire({
        title: 'Generating PDF...',
        text: 'Please wait while we export all document requests',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Fetch all requests data
    fetch('./endpoints/get_all_requests.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success || data.requests.length === 0) {
                Swal.fire({
                    icon: "warning",
                    title: "No data to export",
                    text: "There are no document requests in the database.",
                    confirmButtonColor: '#00247c'
                });
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('landscape'); // Use landscape for more columns

            // Title
            doc.setFontSize(16);
            doc.setFont(undefined, 'bold');
            doc.text("Document Requests Report", 14, 20);

            // Date and time
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            const now = new Date();
            doc.text(`Generated: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}`, 14, 28);

            // Prepare rows from fetched data
            const rows = data.requests.map(request => {
                const requestId = request.request_id || 'N/A';
                const fullName = `${request.first_name || ''} ${request.middle_name || ''} ${request.last_name || ''}`.trim();
                const document = request.document_name || 'N/A';
                const type = request.document_type ? request.document_type.charAt(0).toUpperCase() + request.document_type.slice(1) : 'N/A';
                const purpose = (request.purpose || 'N/A').substring(0, 40) + (request.purpose && request.purpose.length > 40 ? '...' : '');
                const fee = request.fee == 0 ? 'Free' : '₱' + parseFloat(request.fee).toFixed(2);
                const status = request.status ? request.status.charAt(0).toUpperCase() + request.status.slice(1).replace('_', ' ') : 'N/A';
                const submittedDate = request.submitted_date
                    ? new Date(request.submitted_date).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    })
                    : 'N/A';

                return [requestId, fullName, document, type, purpose, fee, status, submittedDate];
            });

            // Create table
            doc.autoTable({
                head: [["Request ID", "Requester", "Document", "Type", "Purpose", "Fee", "Status", "Date"]],
                body: rows,
                startY: 34,
                theme: "grid",
                styles: {
                    fontSize: 8,
                    cellPadding: 2
                },
                headStyles: {
                    fillColor: [0, 36, 124],
                    textColor: 255,
                    fontStyle: 'bold'
                },
                columnStyles: {
                    0: { cellWidth: 28 },  // Request ID
                    1: { cellWidth: 35 },  // Requester
                    2: { cellWidth: 40 },  // Document
                    3: { cellWidth: 25 },  // Type
                    4: { cellWidth: 50 },  // Purpose
                    5: { cellWidth: 25 },  // Fee
                    6: { cellWidth: 30 },  // Status
                    7: { cellWidth: 30 }   // Date
                }
            });

            // Footer with statistics
            const finalY = doc.lastAutoTable.finalY || 34;
            doc.setFontSize(9);
            doc.setFont(undefined, 'bold');
            doc.text(`Total Requests: ${rows.length}`, 14, finalY + 10);

            // Add status breakdown
            const statusCounts = {
                pending: data.requests.filter(r => r.status === 'pending').length,
                processing: data.requests.filter(r => r.status === 'processing').length,
                approved: data.requests.filter(r => r.status === 'approved').length,
                ready: data.requests.filter(r => r.status === 'ready').length,
                completed: data.requests.filter(r => r.status === 'completed').length,
                rejected: data.requests.filter(r => r.status === 'rejected').length,
                cancelled: data.requests.filter(r => r.status === 'cancelled').length
            };

            doc.setFont(undefined, 'normal');
            doc.setFontSize(8);
            doc.text(
                `Pending: ${statusCounts.pending} | Processing: ${statusCounts.processing} | Approved: ${statusCounts.approved} | Ready: ${statusCounts.ready}`,
                14,
                finalY + 16
            );
            doc.text(
                `Completed: ${statusCounts.completed} | Rejected: ${statusCounts.rejected} | Cancelled: ${statusCounts.cancelled}`,
                14,
                finalY + 22
            );

            // Save the PDF
            const filename = `document_requests_report_${new Date().toISOString().split('T')[0]}.pdf`;
            doc.save(filename);

            // Success notification
            Swal.fire({
                icon: 'success',
                title: 'Exported Successfully!',
                text: `${rows.length} request(s) exported to PDF`,
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
                confirmButtonColor: '#00247c'
            });
        });
}