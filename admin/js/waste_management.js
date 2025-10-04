// Tab switching
function switchTab(tab) {
    const params = new URLSearchParams(window.location.search);
    params.set('tab', tab);
    window.location.search = params.toString();
}

// Search schedules
function searchSchedules() {
    const filter = document.getElementById('searchScheduleInput').value.toLowerCase();
    const rows = document.querySelectorAll("#schedulesTable tbody tr");
    let found = false;

    rows.forEach(row => {
        if (row.querySelector('.no-data')) return;

        const wasteType = row.cells[1]?.textContent.toLowerCase() || "";
        const collectionDays = row.cells[2]?.textContent.toLowerCase() || "";
        const description = row.cells[3]?.textContent.toLowerCase() || "";

        const matches = !filter || wasteType.includes(filter) ||
            collectionDays.includes(filter) || description.includes(filter);

        row.style.display = matches ? "" : "none";
        if (matches && filter) found = true;
    });

    handleSearchResults(found, filter, 'schedule');
}

// Search reports
function searchReports() {
    const filter = document.getElementById('searchReportInput').value.toLowerCase();
    const rows = document.querySelectorAll("#reportsTable tbody tr");
    let found = false;

    rows.forEach(row => {
        if (row.querySelector('.no-data')) return;

        const reporter = row.cells[0]?.textContent.toLowerCase() || "";
        const wasteType = row.cells[1]?.textContent.toLowerCase() || "";
        const location = row.cells[2]?.textContent.toLowerCase() || "";
        const status = row.cells[4]?.textContent.toLowerCase() || "";

        const matches = !filter || reporter.includes(filter) ||
            wasteType.includes(filter) || location.includes(filter) ||
            status.includes(filter);

        row.style.display = matches ? "" : "none";
        if (matches && filter) found = true;
    });

    handleSearchResults(found, filter, 'report');
}

function handleSearchResults(found, filter, type) {
    const noDataMsg = document.getElementById(`noData${type}Msg`);
    const pagination = document.querySelector(".pagination-container");

    if (!filter) {
        if (noDataMsg) noDataMsg.style.display = "none";
        if (pagination) pagination.style.display = "flex";
    } else {
        if (!found) {
            if (!noDataMsg) {
                const msg = document.createElement("div");
                msg.id = `noData${type}Msg`;
                msg.className = "no-data";
                msg.innerHTML = `<i class="fas fa-search"></i><p>No ${type}s found matching "${filter}"</p>`;
                document.querySelector(".table-container").appendChild(msg);
            } else {
                noDataMsg.innerHTML = `<i class="fas fa-search"></i><p>No ${type}s found matching "${filter}"</p>`;
                noDataMsg.style.display = "block";
            }
            if (pagination) pagination.style.display = "none";
        } else {
            if (noDataMsg) noDataMsg.style.display = "none";
            if (pagination) pagination.style.display = "flex";
        }
    }
}

// Add Schedule
function addSchedule() {
    Swal.fire({
        title: '<i class="fas fa-calendar-plus"></i> Add Waste Collection Schedule',
        html: `
            <div class="enhanced-form">
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-tag"></i>
                        Waste Type <span style="color: #ef4444;">*</span>
                    </div>
                    <input type="text" class="enhanced-input" id="wasteType" placeholder="e.g., Recyclable Waste" />
                </div>
                
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-calendar-check"></i>
                        Collection Days <span style="color: #ef4444;">*</span>
                    </div>
                    <div class="checkbox-grid">
                        <div class="day-checkbox">
                            <input type="checkbox" id="day-monday" value="Monday">
                            <label for="day-monday">Monday</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="day-tuesday" value="Tuesday">
                            <label for="day-tuesday">Tuesday</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="day-wednesday" value="Wednesday">
                            <label for="day-wednesday">Wednesday</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="day-thursday" value="Thursday">
                            <label for="day-thursday">Thursday</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="day-friday" value="Friday">
                            <label for="day-friday">Friday</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="day-saturday" value="Saturday">
                            <label for="day-saturday">Saturday</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="day-sunday" value="Sunday">
                            <label for="day-sunday">Sunday</label>
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
                            <input type="radio" id="icon-recycle" name="iconClass" value="fa-recycle" checked>
                            <label for="icon-recycle">
                                <i class="fas fa-recycle"></i>
                                <span>Recycle</span>
                            </label>
                        </div>
                        <div class="icon-option">
                            <input type="radio" id="icon-leaf" name="iconClass" value="fa-leaf">
                            <label for="icon-leaf">
                                <i class="fas fa-leaf"></i>
                                <span>Leaf</span>
                            </label>
                        </div>
                        <div class="icon-option">
                            <input type="radio" id="icon-trash" name="iconClass" value="fa-trash">
                            <label for="icon-trash">
                                <i class="fas fa-trash"></i>
                                <span>Trash</span>
                            </label>
                        </div>
                        <div class="icon-option">
                            <input type="radio" id="icon-hospital" name="iconClass" value="fa-hospital">
                            <label for="icon-hospital">
                                <i class="fas fa-hospital"></i>
                                <span>Medical</span>
                            </label>
                        </div>
                        <div class="icon-option">
                            <input type="radio" id="icon-dumpster" name="iconClass" value="fa-dumpster">
                            <label for="icon-dumpster">
                                <i class="fas fa-dumpster"></i>
                                <span>Dumpster</span>
                            </label>
                        </div>
                        <div class="icon-option">
                            <input type="radio" id="icon-biohazard" name="iconClass" value="fa-biohazard">
                            <label for="icon-biohazard">
                                <i class="fas fa-biohazard"></i>
                                <span>Biohazard</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-palette"></i>
                        Color Theme <span style="color: #ef4444;">*</span>
                    </div>
                    <div class="color-grid">
                        <div class="color-option">
                            <input type="radio" id="color-recyclable" name="colorTheme" value="recyclable">
                            <label for="color-recyclable">
                                <div class="color-badge recyclable"></div>
                                <span>Recyclable</span>
                            </label>
                        </div>
                        <div class="color-option">
                            <input type="radio" id="color-biodegradable" name="colorTheme" value="biodegradable" checked>
                            <label for="color-biodegradable">
                                <div class="color-badge biodegradable"></div>
                                <span>Biodegradable</span>
                            </label>
                        </div>
                        <div class="color-option">
                            <input type="radio" id="color-non-biodegradable" name="colorTheme" value="non-biodegradable">
                            <label for="color-non-biodegradable">
                                <div class="color-badge non-biodegradable"></div>
                                <span>Non-Biodegradable</span>
                            </label>
                        </div>
                        <div class="color-option">
                            <input type="radio" id="color-hazardous" name="colorTheme" value="hazardous">
                            <label for="color-hazardous">
                                <div class="color-badge hazardous"></div>
                                <span>Hazardous</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-align-left"></i>
                        Description
                    </div>
                    <textarea class="enhanced-textarea" id="description" placeholder="Brief description of what items belong to this category"></textarea>
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
        confirmButtonText: '<i class="fas fa-plus"></i> Add Schedule',
        cancelButtonText: '<i class="fas fa-times"></i> Cancel',
        confirmButtonColor: '#6366f1',
        cancelButtonColor: '#6b7280',
        preConfirm: () => {
            const wasteType = document.getElementById('wasteType').value.trim();
            const selectedDays = Array.from(document.querySelectorAll('.day-checkbox input:checked'))
                .map(cb => cb.value);
            const iconClass = document.querySelector('input[name="iconClass"]:checked').value;
            const colorTheme = document.querySelector('input[name="colorTheme"]:checked').value;
            const description = document.getElementById('description').value.trim();
            const status = document.querySelector('input[name="status"]:checked').value;

            if (!wasteType) {
                Swal.showValidationMessage('Please enter waste type');
                return false;
            }
            if (selectedDays.length === 0) {
                Swal.showValidationMessage('Please select at least one collection day');
                return false;
            }

            return {
                wasteType,
                collectionDays: selectedDays.join(', '),
                iconClass,
                colorTheme,
                description,
                status
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Adding Schedule...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('./endpoints/add_schedule.php', {
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
                            text: 'Schedule added successfully!',
                            confirmButtonColor: '#6366f1'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message || 'Failed to add schedule'
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

// Edit Schedule with Enhanced UI
function editSchedule(id) {
    Swal.fire({
        title: 'Loading...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(`./endpoints/get_schedule.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const schedule = data.schedule;
                const selectedDays = schedule.collection_days.split(',').map(d => d.trim());

                Swal.fire({
                    title: '<i class="fas fa-edit"></i> Edit Waste Collection Schedule',
                    html: `
                    <div class="enhanced-form">
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-tag"></i>
                                Waste Type <span style="color: #ef4444;">*</span>
                            </div>
                            <input type="text" class="enhanced-input" id="editWasteType" value="${schedule.waste_type}" />
                        </div>
                        
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-calendar-check"></i>
                                Collection Days <span style="color: #ef4444;">*</span>
                            </div>
                            <div class="checkbox-grid">
                                <div class="day-checkbox">
                                    <input type="checkbox" id="edit-day-monday" value="Monday" ${selectedDays.includes('Monday') ? 'checked' : ''}>
                                    <label for="edit-day-monday">Monday</label>
                                </div>
                                <div class="day-checkbox">
                                    <input type="checkbox" id="edit-day-tuesday" value="Tuesday" ${selectedDays.includes('Tuesday') ? 'checked' : ''}>
                                    <label for="edit-day-tuesday">Tuesday</label>
                                </div>
                                <div class="day-checkbox">
                                    <input type="checkbox" id="edit-day-wednesday" value="Wednesday" ${selectedDays.includes('Wednesday') ? 'checked' : ''}>
                                    <label for="edit-day-wednesday">Wednesday</label>
                                </div>
                                <div class="day-checkbox">
                                    <input type="checkbox" id="edit-day-thursday" value="Thursday" ${selectedDays.includes('Thursday') ? 'checked' : ''}>
                                    <label for="edit-day-thursday">Thursday</label>
                                </div>
                                <div class="day-checkbox">
                                    <input type="checkbox" id="edit-day-friday" value="Friday" ${selectedDays.includes('Friday') ? 'checked' : ''}>
                                    <label for="edit-day-friday">Friday</label>
                                </div>
                                <div class="day-checkbox">
                                    <input type="checkbox" id="edit-day-saturday" value="Saturday" ${selectedDays.includes('Saturday') ? 'checked' : ''}>
                                    <label for="edit-day-saturday">Saturday</label>
                                </div>
                                <div class="day-checkbox">
                                    <input type="checkbox" id="edit-day-sunday" value="Sunday" ${selectedDays.includes('Sunday') ? 'checked' : ''}>
                                    <label for="edit-day-sunday">Sunday</label>
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
                                    <input type="radio" id="edit-icon-recycle" name="editIconClass" value="fa-recycle" ${schedule.icon === 'fa-recycle' ? 'checked' : ''}>
                                    <label for="edit-icon-recycle">
                                        <i class="fas fa-recycle"></i>
                                        <span>Recycle</span>
                                    </label>
                                </div>
                                <div class="icon-option">
                                    <input type="radio" id="edit-icon-leaf" name="editIconClass" value="fa-leaf" ${schedule.icon === 'fa-leaf' ? 'checked' : ''}>
                                    <label for="edit-icon-leaf">
                                        <i class="fas fa-leaf"></i>
                                        <span>Leaf</span>
                                    </label>
                                </div>
                                <div class="icon-option">
                                    <input type="radio" id="edit-icon-trash" name="editIconClass" value="fa-trash" ${schedule.icon === 'fa-trash' ? 'checked' : ''}>
                                    <label for="edit-icon-trash">
                                        <i class="fas fa-trash"></i>
                                        <span>Trash</span>
                                    </label>
                                </div>
                                <div class="icon-option">
                                    <input type="radio" id="edit-icon-hospital" name="editIconClass" value="fa-hospital" ${schedule.icon === 'fa-hospital' ? 'checked' : ''}>
                                    <label for="edit-icon-hospital">
                                        <i class="fas fa-hospital"></i>
                                        <span>Medical</span>
                                    </label>
                                </div>
                                <div class="icon-option">
                                    <input type="radio" id="edit-icon-dumpster" name="editIconClass" value="fa-dumpster" ${schedule.icon === 'fa-dumpster' ? 'checked' : ''}>
                                    <label for="edit-icon-dumpster">
                                        <i class="fas fa-dumpster"></i>
                                        <span>Dumpster</span>
                                    </label>
                                </div>
                                <div class="icon-option">
                                    <input type="radio" id="edit-icon-biohazard" name="editIconClass" value="fa-biohazard" ${schedule.icon === 'fa-biohazard' ? 'checked' : ''}>
                                    <label for="edit-icon-biohazard">
                                        <i class="fas fa-biohazard"></i>
                                        <span>Biohazard</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-palette"></i>
                                Color Theme <span style="color: #ef4444;">*</span>
                            </div>
                            <div class="color-grid">
                                <div class="color-option">
                                    <input type="radio" id="edit-color-recyclable" name="editColorTheme" value="recyclable" ${schedule.color === 'recyclable' ? 'checked' : ''}>
                                    <label for="edit-color-recyclable">
                                        <div class="color-badge recyclable"></div>
                                        <span>Recyclable</span>
                                    </label>
                                </div>
                                <div class="color-option">
                                    <input type="radio" id="edit-color-biodegradable" name="editColorTheme" value="biodegradable" ${schedule.color === 'biodegradable' ? 'checked' : ''}>
                                    <label for="edit-color-biodegradable">
                                        <div class="color-badge biodegradable"></div>
                                        <span>Biodegradable</span>
                                    </label>
                                </div>
                                <div class="color-option">
                                    <input type="radio" id="edit-color-non-biodegradable" name="editColorTheme" value="non-biodegradable" ${schedule.color === 'non-biodegradable' ? 'checked' : ''}>
                                    <label for="edit-color-non-biodegradable">
                                        <div class="color-badge non-biodegradable"></div>
                                        <span>Non-Biodegradable</span>
                                    </label>
                                </div>
                                <div class="color-option">
                                    <input type="radio" id="edit-color-hazardous" name="editColorTheme" value="hazardous" ${schedule.color === 'hazardous' ? 'checked' : ''}>
                                    <label for="edit-color-hazardous">
                                        <div class="color-badge hazardous"></div>
                                        <span>Hazardous</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-align-left"></i>
                                Description
                            </div>
                            <textarea class="enhanced-textarea" id="editDescription">${schedule.description || ''}</textarea>
                        </div>
                        
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-toggle-on"></i>
                                Status
                            </div>
                            <div class="status-toggle">
                                <div class="status-toggle-option">
                                    <input type="radio" id="edit-status-active" name="editStatus" value="1" ${schedule.is_active == 1 ? 'checked' : ''}>
                                    <label for="edit-status-active"><i class="fas fa-check-circle"></i> Active</label>
                                </div>
                                <div class="status-toggle-option">
                                    <input type="radio" id="edit-status-inactive" name="editStatus" value="0" ${schedule.is_active == 0 ? 'checked' : ''}>
                                    <label for="edit-status-inactive" class="inactive-label"><i class="fas fa-times-circle"></i> Inactive</label>
                                </div>
                            </div>
                        </div>
                    </div>
                `,
                    width: '650px',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-save"></i> Update Schedule',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#6b7280',
                    preConfirm: () => {
                        const wasteType = document.getElementById('editWasteType').value.trim();
                        const selectedDays = Array.from(document.querySelectorAll('.day-checkbox input:checked'))
                            .map(cb => cb.value);
                        const iconClass = document.querySelector('input[name="editIconClass"]:checked').value;
                        const colorTheme = document.querySelector('input[name="editColorTheme"]:checked').value;
                        const description = document.getElementById('editDescription').value.trim();
                        const status = document.querySelector('input[name="editStatus"]:checked').value;

                        if (!wasteType) {
                            Swal.showValidationMessage('Please enter waste type');
                            return false;
                        }
                        if (selectedDays.length === 0) {
                            Swal.showValidationMessage('Please select at least one collection day');
                            return false;
                        }

                        return {
                            id,
                            wasteType,
                            collectionDays: selectedDays.join(', '),
                            iconClass,
                            colorTheme,
                            description,
                            status
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Updating Schedule...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        fetch('./endpoints/edit_schedule.php', {
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
                                        text: 'Schedule updated successfully!',
                                        confirmButtonColor: '#6366f1'
                                    }).then(() => location.reload());
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: data.message || 'Failed to update schedule'
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
                    text: data.message || 'Failed to fetch schedule'
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

// Delete Schedule
function deleteSchedule(id) {
    Swal.fire({
        title: 'Delete Schedule',
        text: 'Are you sure you want to delete this schedule?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('./endpoints/delete_schedule.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Deleted!', 'Schedule has been deleted.', 'success')
                            .then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message || 'Failed to delete schedule', 'error');
                    }
                })
                .catch(() => {
                    Swal.fire('Error', 'Unable to connect to server', 'error');
                });
        }
    });
}

// View Report 
function viewReport(id) {
    Swal.fire({
        title: 'Loading...',
        text: 'Fetching report information.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`./endpoints/get_report.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const report = data.report;

                // Format dates
                const collectionDate = report.collection_date ? new Date(report.collection_date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                }) : 'N/A';

                const createdDate = report.created_at ? new Date(report.created_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }) : 'N/A';

                const resolvedDate = report.resolved_date ? new Date(report.resolved_date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }) : 'N/A';

                // Get reporter profile image path
                const profileImage = report.image ?
                    `../assets/images/user/${report.image}` :
                    '../assets/images/user.png';

                // Format status with color - Updated for waste report statuses
                const getStatusStyle = (status) => {
                    switch (status) {
                        case 'resolved':
                            return 'background: #d1fae5; color: #059669;';
                        case 'rejected':
                            return 'background: #fee2e2; color: #dc2626;';
                        case 'investigating':
                            return 'background: #ddd6fe; color: #7c3aed;';
                        case 'pending':
                            return 'background: #fef3c7; color: #d97706;';
                        default:
                            return 'background: #fef3c7; color: #d97706;';
                    }
                };

                // Photo section
                const photoHtml = report.photo_path ?
                    `<div class="section-header-simple">Attached Photo</div>
                <div class="photo-container-simple" style="text-align: center; padding: 10px 0;">
                    <img src="../${report.photo_path}" alt="Report Photo" style="max-width: 100%; border-radius: 8px; border: 1px solid #e5e7eb;">
                </div>` : '';

                Swal.fire({
                    title: 'Missed Collection Report Details',
                    html: `
                    <div class="resident-details-container-simple">
                        <!-- Reporter Header with Profile Image -->
                        <div class="profile-header-simple">
                            <img src="${profileImage}" alt="Profile" class="resident-profile-image-simple">
                            <div class="profile-info-simple">
                                <div class="resident-name-simple">
                                    ${report.first_name} ${report.middle_name || ''} ${report.last_name}
                                </div>
                                <div class="resident-email-simple">${report.email || 'N/A'}</div>
                                <div style="display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-top: 8px; ${getStatusStyle(report.status)}">
                                    ${report.status.toUpperCase()}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reporter Information Section -->
                        <div class="section-header-simple">Reporter Information</div>
                        <div class="resident-info-grid-simple">
                            <div class="info-item-simple">
                                <div class="info-label-simple">First Name</div>
                                <div class="info-value-simple">${report.first_name || 'N/A'}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Middle Name</div>
                                <div class="info-value-simple">${report.middle_name || 'N/A'}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Last Name</div>
                                <div class="info-value-simple">${report.last_name || 'N/A'}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Email Address</div>
                                <div class="info-value-simple">${report.email || 'N/A'}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Contact Number</div>
                                <div class="info-value-simple">${report.contact_number || 'N/A'}</div>
                            </div>
                        </div>
                        
                        <!-- Report Details Section -->
                        <div class="section-header-simple">Report Details</div>
                        <div class="resident-info-grid-simple">
                            <div class="info-item-simple">
                                <div class="info-label-simple">Waste Type</div>
                                <div class="info-value-simple">${report.waste_type}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Missed Collection Date</div>
                                <div class="info-value-simple">${collectionDate}</div>
                            </div>
                            
                            <div class="info-item-simple full-width">
                                <div class="info-label-simple">Location</div>
                                <div class="info-value-simple">${report.location}</div>
                            </div>
                            
                            <div class="info-item-simple full-width">
                                <div class="info-label-simple">Description</div>
                                <div class="info-value-simple">${report.description || 'No description provided'}</div>
                            </div>
                        </div>
                        
                        ${photoHtml}
                        
                        <!-- Status Information Section -->
                        <div class="section-header-simple">Status Information</div>
                        <div class="resident-info-grid-simple">
                            <div class="info-item-simple">
                                <div class="info-label-simple">Current Status</div>
                                <div class="info-value-simple">
                                    <span style="display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; ${getStatusStyle(report.status)}">
                                        ${report.status.charAt(0).toUpperCase() + report.status.slice(1)}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Date Reported</div>
                                <div class="info-value-simple">${createdDate}</div>
                            </div>
                            
                            ${report.resolved_date ? `
                            <div class="info-item-simple">
                                <div class="info-label-simple">Date Resolved</div>
                                <div class="info-value-simple">${resolvedDate}</div>
                            </div>
                            ` : ''}
                            
                            ${report.resolution_notes ? `
                            <div class="info-item-simple full-width">
                                <div class="info-label-simple">Resolution Notes</div>
                                <div class="info-value-simple">${report.resolution_notes}</div>
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

// Update Report Status
function updateReportStatus(id) {
    Swal.fire({
        title: 'Loading...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(`./endpoints/get_report.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const report = data.report;
                Swal.fire({
                    title: '<i class="fas fa-edit"></i> Update Report Status',
                    html: `
                    <div class="enhanced-form">
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-info-circle"></i>
                                Current Status
                            </div>
                            <div style="padding: 12px; background: #f3f4f6; border: 2px solid #e5e7eb; border-radius: 8px; margin-top: 8px;">
                                <span class="status-badge ${report.status}">${report.status.charAt(0).toUpperCase() + report.status.slice(1)}</span>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-sync-alt"></i>
                                New Status <span style="color: #ef4444;">*</span>
                            </div>
                            <select class="enhanced-input" id="newStatus" style="margin-top: 8px;">
                                <option value="pending" ${report.status === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="investigating" ${report.status === 'investigating' ? 'selected' : ''}>Investigating</option>
                                <option value="resolved" ${report.status === 'resolved' ? 'selected' : ''}>Resolved</option>
                                <option value="rejected" ${report.status === 'rejected' ? 'selected' : ''}>Rejected</option>
                            </select>
                        </div>
                        
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-file-alt"></i>
                                Resolution Notes
                            </div>
                            <textarea class="enhanced-textarea" id="resolutionNotes" rows="4" 
                                      placeholder="Add notes about the resolution or action taken...">${report.resolution_notes || ''}</textarea>
                        </div>
                    </div>
                `,
                    width: '650px',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-save"></i> Update Status',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#6b7280',
                    preConfirm: () => {
                        const newStatus = document.getElementById('newStatus').value;
                        const resolutionNotes = document.getElementById('resolutionNotes').value.trim();
                        return { id, newStatus, resolutionNotes };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Updating Status...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        fetch('./endpoints/update_report_status.php', {
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
                                        confirmButtonColor: '#6366f1'
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
function deleteReport(id) {
    Swal.fire({
        title: 'Delete Report',
        text: 'Are you sure you want to delete this report?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('./endpoints/delete_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Deleted!', 'Report has been deleted.', 'success')
                            .then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message || 'Failed to delete report', 'error');
                    }
                })
                .catch(() => {
                    Swal.fire('Error', 'Unable to connect to server', 'error');
                });
        }
    });
}

// Export Data
function exportData() {
    const activeTab = new URLSearchParams(window.location.search).get('tab') || 'schedules';

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    doc.setFontSize(14);
    doc.text(activeTab === 'schedules' ? "Waste Collection Schedules" : "Missed Collection Reports", 14, 20);

    const rows = [];

    if (activeTab === 'schedules') {
        document.querySelectorAll("#schedulesTable tbody tr").forEach(row => {
            if (row.style.display !== "none" && !row.querySelector('.no-data')) {
                const wasteType = row.cells[1]?.textContent.trim() || "";
                const days = row.cells[2]?.textContent.trim() || "";
                const status = row.cells[4]?.textContent.trim() || "";
                rows.push([wasteType, days, status]);
            }
        });

        if (rows.length === 0) {
            Swal.fire({
                icon: "warning",
                title: "No data to export",
                text: "There are no schedules matching your current filters.",
            });
            return;
        }

        doc.autoTable({
            head: [["Waste Type", "Collection Days", "Status"]],
            body: rows,
            startY: 30,
            theme: "grid"
        });
    } else {
        document.querySelectorAll("#reportsTable tbody tr").forEach(row => {
            if (row.style.display !== "none" && !row.querySelector('.no-data')) {
                const reporter = row.cells[0]?.textContent.trim().split('\n')[0] || "";
                const wasteType = row.cells[1]?.textContent.trim() || "";
                const location = row.cells[2]?.textContent.trim() || "";
                const status = row.cells[4]?.textContent.trim() || "";
                rows.push([reporter, wasteType, location, status]);
            }
        });

        if (rows.length === 0) {
            Swal.fire({
                icon: "warning",
                title: "No data to export",
                text: "There are no reports matching your current filters.",
            });
            return;
        }

        doc.autoTable({
            head: [["Reporter", "Waste Type", "Location", "Status"]],
            body: rows,
            startY: 30,
            theme: "grid"
        });
    }

    doc.save(`waste_management_${activeTab}.pdf`);
}