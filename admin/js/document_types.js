// Add Document Type
function addDocumentType() {
    Swal.fire({
        title: '<i class="fas fa-file-medical-alt"></i> Add Document Type',
        html: `
            <div class="enhanced-form">
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-file-alt"></i>
                        Document Name <span style="color: #ef4444;">*</span>
                    </div>
                    <input type="text" class="enhanced-input" id="documentName" placeholder="e.g., Barangay Clearance" />
                </div>
                
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-tag"></i>
                        Document Type <span style="color: #ef4444;">*</span>
                    </div>
                    <div class="status-toggle">
                        <div class="status-toggle-option">
                            <input type="radio" id="type-certificate" name="documentType" value="certificate" checked>
                            <label for="type-certificate"><i class="fas fa-certificate"></i> Certificate</label>
                        </div>
                        <div class="status-toggle-option">
                            <input type="radio" id="type-permit" name="documentType" value="permit">
                            <label for="type-permit"><i class="fas fa-id-card"></i> Permit</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-icons"></i>
                        Select Icon <span style="color: #ef4444;">*</span>
                    </div>
                    <div class="icon-grid">
                        <div class="icon-option">
                            <input type="radio" id="icon-certificate" name="iconClass" value="fa-certificate" checked>
                            <label for="icon-certificate">
                                <i class="fas fa-certificate"></i>
                                <span>Certificate</span>
                            </label>
                        </div>
                        <div class="icon-option">
                            <input type="radio" id="icon-id-card" name="iconClass" value="fa-id-card">
                            <label for="icon-id-card">
                                <i class="fas fa-id-card"></i>
                                <span>ID Card</span>
                            </label>
                        </div>
                        <div class="icon-option">
                            <input type="radio" id="icon-file-alt" name="iconClass" value="fa-file-alt">
                            <label for="icon-file-alt">
                                <i class="fas fa-file-alt"></i>
                                <span>Document</span>
                            </label>
                        </div>
                        <div class="icon-option">
                            <input type="radio" id="icon-briefcase" name="iconClass" value="fa-briefcase">
                            <label for="icon-briefcase">
                                <i class="fas fa-briefcase"></i>
                                <span>Business</span>
                            </label>
                        </div>
                        <div class="icon-option">
                            <input type="radio" id="icon-home" name="iconClass" value="fa-home">
                            <label for="icon-home">
                                <i class="fas fa-home"></i>
                                <span>Residence</span>
                            </label>
                        </div>
                        <div class="icon-option">
                            <input type="radio" id="icon-user-check" name="iconClass" value="fa-user-check">
                            <label for="icon-user-check">
                                <i class="fas fa-user-check"></i>
                                <span>Indigency</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-clock"></i>
                        Processing Days <span style="color: #ef4444;">*</span>
                    </div>
                    <input type="text" class="enhanced-input" id="processingDays" placeholder="e.g., 3-5 days" />
                </div>
                
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-money-bill-wave"></i>
                        Fee (PHP) <span style="color: #ef4444;">*</span>
                    </div>
                    <input type="number" class="enhanced-input" id="documentFee" placeholder="0.00" step="0.01" min="0" value="0" />
                </div>
                
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-align-left"></i>
                        Description
                    </div>
                    <textarea class="enhanced-textarea" id="description" placeholder="Brief description of this document type"></textarea>
                </div>
                
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-toggle-on"></i>
                        Status
                    </div>
                    <div class="status-toggle">
                        <div class="status-toggle-option">
                            <input type="radio" id="status-active" name="status" value="1" checked>
                            <label for="status-active"><i class="fas fa-check-circle"></i> Active</label>
                        </div>
                        <div class="status-toggle-option">
                            <input type="radio" id="status-inactive" name="status" value="0">
                            <label for="status-inactive" class="inactive-label"><i class="fas fa-times-circle"></i> Inactive</label>
                        </div>
                    </div>
                </div>
            </div>
        `,
        width: '650px',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-plus"></i> Add Document',
        cancelButtonText: '<i class="fas fa-times"></i> Cancel',
        confirmButtonColor: '#6366f1',
        cancelButtonColor: '#6b7280',
        preConfirm: () => {
            const documentName = document.getElementById('documentName').value.trim();
            const documentType = document.querySelector('input[name="documentType"]:checked').value;
            const iconClass = document.querySelector('input[name="iconClass"]:checked').value;
            const processingDays = document.getElementById('processingDays').value.trim();
            const fee = document.getElementById('documentFee').value;
            const description = document.getElementById('description').value.trim();
            // const requirements = document.getElementById('requirements').value.trim();
            const status = document.querySelector('input[name="status"]:checked').value;

            if (!documentName) {
                Swal.showValidationMessage('Please enter document name');
                return false;
            }
            if (!processingDays) {
                Swal.showValidationMessage('Please enter processing days');
                return false;
            }

            return {
                documentName,
                documentType,
                iconClass,
                processingDays,
                fee,
                description,
                // requirements,
                status
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Adding Document Type...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('./endpoints/add_document_type.php', {
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
                            text: 'Document type added successfully!',
                            confirmButtonColor: '#6366f1'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message || 'Failed to add document type'
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

// View Document Type Details
function viewDocumentType(id) {
    Swal.fire({
        title: 'Loading...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(`./endpoints/get_document_type.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const doc = data.document;
                Swal.fire({
                    title: '<i class="fas fa-file-alt"></i> Document Type Details',
                    html: `
                        <div class="enhanced-form" style="text-align: left;">
                            <div class="form-section">
                                <div class="section-title"><i class="fas ${doc.icon}"></i> ${doc.name}</div>
                                <div style="padding: 10px 0;">
                                    <span class="status-badge ${doc.type === 'certificate' ? 'certificate' : 'permit'}">
                                        ${doc.type.charAt(0).toUpperCase() + doc.type.slice(1)}
                                    </span>
                                    <span class="status-badge ${doc.is_active == 1 ? 'active' : 'inactive'}">
                                        ${doc.is_active == 1 ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="section-title"><i class="fas fa-money-bill-wave"></i> Fee</div>
                                <div>${doc.fee == 0 ? '<span style="color: #28a745; font-weight: 600;">Free</span>' : '₱' + parseFloat(doc.fee).toFixed(2)}</div>
                            </div>
                            
                            <div class="form-section">
                                <div class="section-title"><i class="fas fa-clock"></i> Processing Time</div>
                                <div>${doc.processing_days}</div>
                            </div>
                            
                            ${doc.description ? `
                            <div class="form-section">
                                <div class="section-title"><i class="fas fa-align-left"></i> Description</div>
                                <div>${doc.description}</div>
                            </div>
                            ` : ''}
                            
                            ${doc.requirements ? `
                            <div class="form-section">
                                <div class="section-title"><i class="fas fa-list-ul"></i> Requirements</div>
                                <div style="white-space: pre-line;">${doc.requirements}</div>
                            </div>
                            ` : ''}
                        </div>
                    `,
                    width: '600px',
                    confirmButtonText: 'Close',
                    confirmButtonColor: '#6366f1'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to fetch document type'
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

// Edit Document Type
function editDocumentType(id) {
    Swal.fire({
        title: 'Loading...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(`./endpoints/get_document_type.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const doc = data.document;

                Swal.fire({
                    title: '<i class="fas fa-edit"></i> Edit Document Type',
                    html: `
                        <div class="enhanced-form">
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-file-alt"></i>
                                    Document Name <span style="color: #ef4444;">*</span>
                                </div>
                                <input type="text" class="enhanced-input" id="editDocumentName" value="${doc.name}" />
                            </div>
                            
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-tag"></i>
                                    Document Type <span style="color: #ef4444;">*</span>
                                </div>
                                <div class="status-toggle">
                                    <div class="status-toggle-option">
                                        <input type="radio" id="edit-type-certificate" name="editDocumentType" value="certificate" ${doc.type === 'certificate' ? 'checked' : ''}>
                                        <label for="edit-type-certificate"><i class="fas fa-certificate"></i> Certificate</label>
                                    </div>
                                    <div class="status-toggle-option">
                                        <input type="radio" id="edit-type-permit" name="editDocumentType" value="permit" ${doc.type === 'permit' ? 'checked' : ''}>
                                        <label for="edit-type-permit"><i class="fas fa-id-card"></i> Permit</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-icons"></i>
                                    Select Icon <span style="color: #ef4444;">*</span>
                                </div>
                                <div class="icon-grid">
                                    <div class="icon-option">
                                        <input type="radio" id="edit-icon-certificate" name="editIconClass" value="fa-certificate" ${doc.icon === 'fa-certificate' ? 'checked' : ''}>
                                        <label for="edit-icon-certificate">
                                            <i class="fas fa-certificate"></i>
                                            <span>Certificate</span>
                                        </label>
                                    </div>
                                    <div class="icon-option">
                                        <input type="radio" id="edit-icon-id-card" name="editIconClass" value="fa-id-card" ${doc.icon === 'fa-id-card' ? 'checked' : ''}>
                                        <label for="edit-icon-id-card">
                                            <i class="fas fa-id-card"></i>
                                            <span>ID Card</span>
                                        </label>
                                    </div>
                                    <div class="icon-option">
                                        <input type="radio" id="edit-icon-file-alt" name="editIconClass" value="fa-file-alt" ${doc.icon === 'fa-file-alt' ? 'checked' : ''}>
                                        <label for="edit-icon-file-alt">
                                            <i class="fas fa-file-alt"></i>
                                            <span>Document</span>
                                        </label>
                                    </div>
                                    <div class="icon-option">
                                        <input type="radio" id="edit-icon-briefcase" name="editIconClass" value="fa-briefcase" ${doc.icon === 'fa-briefcase' ? 'checked' : ''}>
                                        <label for="edit-icon-briefcase">
                                            <i class="fas fa-briefcase"></i>
                                            <span>Business</span>
                                        </label>
                                    </div>
                                    <div class="icon-option">
                                        <input type="radio" id="edit-icon-home" name="editIconClass" value="fa-home" ${doc.icon === 'fa-home' ? 'checked' : ''}>
                                        <label for="edit-icon-home">
                                            <i class="fas fa-home"></i>
                                            <span>Residence</span>
                                        </label>
                                    </div>
                                    <div class="icon-option">
                                        <input type="radio" id="edit-icon-user-check" name="editIconClass" value="fa-user-check" ${doc.icon === 'fa-user-check' ? 'checked' : ''}>
                                        <label for="edit-icon-user-check">
                                            <i class="fas fa-user-check"></i>
                                            <span>Indigency</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-clock"></i>
                                    Processing Days <span style="color: #ef4444;">*</span>
                                </div>
                                <input type="text" class="enhanced-input" id="editProcessingDays" value="${doc.processing_days || ''}" />
                            </div>
                            
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-money-bill-wave"></i>
                                    Fee (PHP) <span style="color: #ef4444;">*</span>
                                </div>
                                <input type="number" class="enhanced-input" id="editDocumentFee" value="${doc.fee}" step="0.01" min="0" />
                            </div>
                            
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-align-left"></i>
                                    Description
                                </div>
                                <textarea class="enhanced-textarea" id="editDescription">${doc.description || ''}</textarea>
                            </div>
                            
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-toggle-on"></i>
                                    Status
                                </div>
                                <div class="status-toggle">
                                    <div class="status-toggle-option">
                                        <input type="radio" id="edit-status-active" name="editStatus" value="1" ${doc.is_active == 1 ? 'checked' : ''}>
                                        <label for="edit-status-active"><i class="fas fa-check-circle"></i> Active</label>
                                    </div>
                                    <div class="status-toggle-option">
                                        <input type="radio" id="edit-status-inactive" name="editStatus" value="0" ${doc.is_active == 0 ? 'checked' : ''}>
                                        <label for="edit-status-inactive" class="inactive-label"><i class="fas fa-times-circle"></i> Inactive</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `,
                    width: '650px',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-save"></i> Update Document',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#6b7280',
                    preConfirm: () => {
                        const documentName = document.getElementById('editDocumentName').value.trim();
                        const documentType = document.querySelector('input[name="editDocumentType"]:checked').value;
                        const iconClass = document.querySelector('input[name="editIconClass"]:checked').value;
                        const processingDays = document.getElementById('editProcessingDays').value.trim();
                        const fee = document.getElementById('editDocumentFee').value;
                        const description = document.getElementById('editDescription').value.trim();
                        // const requirements = document.getElementById('editRequirements').value.trim();
                        const status = document.querySelector('input[name="editStatus"]:checked').value;

                        if (!documentName) {
                            Swal.showValidationMessage('Please enter document name');
                            return false;
                        }
                        if (!processingDays) {
                            Swal.showValidationMessage('Please enter processing days');
                            return false;
                        }

                        return {
                            id,
                            documentName,
                            documentType,
                            iconClass,
                            processingDays,
                            fee,
                            description,
                            // requirements,
                            status
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Updating Document Type...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        fetch('./endpoints/edit_document_type.php', {
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
                                        text: 'Document type updated successfully!',
                                        confirmButtonColor: '#6366f1'
                                    }).then(() => location.reload());
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: data.message || 'Failed to update document type'
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
                    text: data.message || 'Failed to fetch document type'
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

// Delete Document Type
function deleteDocumentType(id) {
    Swal.fire({
        title: 'Delete Document Type',
        text: 'Are you sure you want to delete this document type? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleting...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('./endpoints/delete_document_type.php', {
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
                            text: 'Document type has been deleted.',
                            confirmButtonColor: '#6366f1'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to delete document type'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Unable to connect to server'
                    });
                });
        }
    });
}