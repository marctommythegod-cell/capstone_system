// js/functions.js - JavaScript Functionality

// Sidebar toggle functionality
function toggleSidebar() {
    const container = document.querySelector('.dashboard-container');
    container.classList.toggle('sidebar-collapsed');
    
    // Store the state in localStorage
    const isCollapsed = container.classList.contains('sidebar-collapsed');
    localStorage.setItem('sidebarCollapsed', isCollapsed);
}

// Close sidebar on mobile/tablet when nav item is clicked
function closeSidebarOnMobile() {
    // Only close sidebar on mobile and tablet (screen width <= 768px)
    if (window.innerWidth <= 768) {
        const container = document.querySelector('.dashboard-container');
        container.classList.add('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', true);
    }
}

// Initialize sidebar state on page load
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.dashboard-container');
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    if (isCollapsed && container) {
        container.classList.add('sidebar-collapsed');
    }
    
    // Add click event listeners to submenu items only (not submenu-trigger)
    const submenuItems = document.querySelectorAll('.submenu-item');
    submenuItems.forEach(item => {
        item.addEventListener('click', closeSidebarOnMobile);
    });
    
    // Add click event listeners to regular nav items (excluding submenu-trigger)
    const navItems = document.querySelectorAll('.nav-item:not(.submenu-trigger)');
    navItems.forEach(item => {
        item.addEventListener('click', closeSidebarOnMobile);
    });
});

// Logout confirmation modal
function showLogoutModal() {
    // Remove existing modal if any
    const existing = document.getElementById('logoutModal');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'logoutModal';
    modal.className = 'logout-modal';
    modal.innerHTML = `
        <div class="logout-modal-box">
            <div class="logout-modal-header">
                <h3>Confirm Logout</h3>
            </div>
            <div class="logout-modal-body">
                <p>Are you sure you want to log out of the system?</p>
            </div>
            <div class="logout-modal-footer">
                <button class="btn btn-secondary" onclick="closeLogoutModal()">Cancel</button>
                <button class="btn btn-danger" onclick="window.location.href='/CLASS_CARD_DROPPING_SYSTEM/backend/includes/logout.php'">Yes, Logout</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Close on backdrop click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeLogoutModal();
    });

    // Close on Escape key
    document.addEventListener('keydown', function handler(e) {
        if (e.key === 'Escape') {
            closeLogoutModal();
            document.removeEventListener('keydown', handler);
        }
    });
}

function closeLogoutModal() {
    const modal = document.getElementById('logoutModal');
    if (modal) modal.remove();
}

// Live table filter - filters table rows as user types
function liveTableFilter(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;

    input.addEventListener('input', function() {
        const filter = this.value.toLowerCase().trim();
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        const rows = tbody.querySelectorAll('tr');
        let visibleCount = 0;

        rows.forEach(function(row) {
            const cells = row.querySelectorAll('td');
            let match = false;
            cells.forEach(function(cell) {
                if (cell.textContent.toLowerCase().includes(filter)) {
                    match = true;
                }
            });
            row.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        // Update counter if exists
        const counter = document.getElementById(tableId + '-count');
        if (counter) {
            counter.textContent = visibleCount;
        }
    });
}

// Initialize all live filters on page load
document.addEventListener('DOMContentLoaded', function() {
    // Auto-init any live filter pairs found on the page
    var filters = document.querySelectorAll('[data-live-filter]');
    filters.forEach(function(input) {
        liveTableFilter(input.id, input.getAttribute('data-live-filter'));
    });
});

// Update student info when selected
function updateStudentInfo() {
    const select = document.getElementById('student_id');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        document.getElementById('course').value = selectedOption.dataset.course;
        document.getElementById('year').value = selectedOption.dataset.year;
    } else {
        document.getElementById('course').value = '';
        document.getElementById('year').value = '';
    }
}

// Update subject info when selected
function updateSubjectInfo() {
    const select = document.getElementById('subject_id');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        // Subject name is already shown in the option, nothing else to update
    }
}

// Custom confirmation modal (replaces browser confirm dialogs)
function showConfirmModal(message, onConfirm) {
    // Remove any existing modal
    const existing = document.getElementById('customConfirmModal');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'customConfirmModal';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(4px);
        padding: 20px;
    `;

    const box = document.createElement('div');
    box.style.cssText = `
        background: #fff;
        border-radius: 14px;
        padding: 0;
        width: 100%;
        max-width: 500px;
        max-height: 85vh;
        overflow-y: auto;
        box-shadow: 0 12px 48px rgba(0, 0, 0, 0.22);
    `;

    box.innerHTML = `
        <div style="
            background: linear-gradient(135deg, #7f3fc6 0%, #a78bfa 100%);
            color: white;
            padding: 32px;
            border-radius: 14px 14px 0 0;
            box-shadow: 0 4px 12px rgba(127, 63, 198, 0.2);
        ">
            <h2 style="margin: 0; font-size: 1.35em; font-weight: 700;">Confirm Action</h2>
        </div>

        <div style="padding: 32px;">
            <div style="
                background: #f8f7ff;
                border: 2px solid #e9d5ff;
                border-radius: 12px;
                padding: 16px;
                color: #5a3d7a;
                font-size: 0.95em;
                line-height: 1.5;
                white-space: pre-wrap;
            ">${message}</div>
        </div>

        <div style="
            padding: 20px 32px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            background: #f9fafb;
            border-radius: 0 0 14px 14px;
        ">
            <button id="confirmModalCancel" style="
                padding: 10px 24px;
                border: 2px solid #d1d5db;
                background: white;
                color: #374151;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.95em;
                transition: all 0.2s;
            " onmouseover="this.style.background='#f3f4f6'; this.style.borderColor='#9ca3af';" onmouseout="this.style.background='white'; this.style.borderColor='#d1d5db';">Cancel</button>
            <button id="confirmModalOk" style="
                padding: 10px 28px;
                border: none;
                background: linear-gradient(135deg, #7f3fc6 0%, #a78bfa 100%);
                color: white;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.95em;
                transition: all 0.3s;
                box-shadow: 0 4px 12px rgba(127, 63, 198, 0.3);
            " onmouseover="this.style.boxShadow='0 6px 16px rgba(127, 63, 198, 0.4)'; this.style.transform='translateY(-1px)';" onmouseout="this.style.boxShadow='0 4px 12px rgba(127, 63, 198, 0.3)'; this.style.transform='translateY(0)';">Confirm</button>
        </div>
    `;

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    document.getElementById('confirmModalCancel').onclick = function() {
        overlay.remove();
    };
    document.getElementById('confirmModalOk').onclick = function() {
        overlay.remove();
        if (onConfirm) onConfirm();
    };
    
    // Click outside to close
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.remove();
    });
    
    // ESC key to close
    const escapeHandler = function(e) {
        if (e.key === 'Escape') {
            overlay.remove();
            document.removeEventListener('keydown', escapeHandler);
        }
    };
    document.addEventListener('keydown', escapeHandler);
}

// Undrop modal with remarks textarea and certificate checkboxes - Modern Design
function showUndropModal(dropId, originalRemarks = '') {
    const existing = document.getElementById('undropModal');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'undropModal';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(4px);
        padding: 20px;
    `;

    const box = document.createElement('div');
    box.style.cssText = `
        background: #fff;
        border-radius: 14px;
        padding: 0;
        width: 100%;
        max-width: 600px;
        max-height: 85vh;
        overflow-y: auto;
        box-shadow: 0 12px 48px rgba(0, 0, 0, 0.22);
    `;

    box.innerHTML = `
        <div style="
            background: linear-gradient(135deg, #7f3fc6 0%, #a78bfa 100%);
            color: white;
            padding: 32px;
            border-radius: 14px 14px 0 0;
            box-shadow: 0 4px 12px rgba(127, 63, 198, 0.2);
        ">
            <h2 style="margin: 0 0 4px; font-size: 1.35em; font-weight: 700;">Undrop Class Card</h2>
            <p style="margin: 0; opacity: 0.95; font-size: 0.9em;">Restore this class card and provide the necessary documentation</p>
        </div>

        <div style="padding: 32px;">
            <div style="
                background: #f8f7ff;
                border: 2px solid #e9d5ff;
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 24px;
                display: flex;
                align-items: flex-start;
                gap: 12px;
            ">
                <p style="margin: 0; color: #5a3d7a; font-size: 0.95em; line-height: 1.5;">
                    Please provide the reason for restoring this class card and add any relevant remarks for the teacher's record.
                </p>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="
                    display: block;
                    margin-bottom: 12px;
                    font-weight: 700;
                    color: #1f2937;
                    font-size: 0.95em;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                ">Reason for Absent</label>
                
                <div style="
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 10px;
                    margin-bottom: 12px;
                ">
                    <label style="
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        padding: 12px 14px;
                        background: #f3f4f6;
                        border: 2px solid transparent;
                        border-radius: 10px;
                        cursor: pointer;
                        transition: all 0.2s;
                    " class="undrop-cert-label">
                        <input type="checkbox" id="undropCert_medical" name="undrop_certificates" value="Medical Certificate" style="
                            width: 18px;
                            height: 18px;
                            cursor: pointer;
                            accent-color: #7f3fc6;
                        ">
                        <span style="font-weight: 500; color: #374151; flex: 1;">Medical Certificate</span>
                    </label>
                    
                    <label style="
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        padding: 12px 14px;
                        background: #f3f4f6;
                        border: 2px solid transparent;
                        border-radius: 10px;
                        cursor: pointer;
                        transition: all 0.2s;
                    " class="undrop-cert-label">
                        <input type="checkbox" id="undropCert_parents" name="undrop_certificates" value="Parents Letter" style="
                            width: 18px;
                            height: 18px;
                            cursor: pointer;
                            accent-color: #7f3fc6;
                        ">
                        <span style="font-weight: 500; color: #374151; flex: 1;">Parents Letter</span>
                    </label>
                </div>

                <label style="
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 12px 14px;
                    background: #f3f4f6;
                    border: 2px solid transparent;
                    border-radius: 10px;
                    cursor: pointer;
                    transition: all 0.2s;
                    margin-bottom: 10px;
                " class="undrop-cert-label">
                    <input type="checkbox" id="undropCert_other" name="undrop_certificates" value="Other" style="
                        width: 18px;
                        height: 18px;
                        cursor: pointer;
                        accent-color: #7f3fc6;
                    " onchange="toggleOtherField()">
                    <span style="font-weight: 500; color: #374151; flex: 1;">Other</span>
                </label>

                <input type="text" id="undropOtherText" placeholder="Please specify the reason..." style="
                    display: none;
                    width: 100%;
                    padding: 10px 14px;
                    border: 2px solid #e5e7eb;
                    border-radius: 8px;
                    font-size: 0.9em;
                    box-sizing: border-box;
                    transition: all 0.2s;
                " onfocus="this.style.borderColor='#7f3fc6'; this.style.boxShadow='0 0 0 3px rgba(127, 63, 198, 0.1)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                
                <p id="undropCertificatesError" style="
                    color: #dc2626;
                    font-size: 0.85em;
                    margin: 8px 0 0;
                    display: none;
                    font-weight: 500;
                ">⚠️ Please select at least one reason for undrop.</p>
            </div>

            <div style="margin-bottom: 8px;">
                <label for="undropRemarks" style="
                    display: block;
                    margin-bottom: 10px;
                    font-weight: 700;
                    color: #1f2937;
                    font-size: 0.95em;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                ">Admin Remarks <span style="color: #a78bfa; font-weight: 400; text-transform: none; letter-spacing: 0;">(required)</span></label>
                
                <textarea id="undropRemarks" rows="5" placeholder="Provide detailed remarks for the teacher..." style="
                    width: 100%;
                    padding: 12px 14px;
                    border: 2px solid #e5e7eb;
                    border-radius: 8px;
                    font-size: 0.9em;
                    resize: none;
                    box-sizing: border-box;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    transition: all 0.2s;
                " onfocus="this.style.borderColor='#7f3fc6'; this.style.boxShadow='0 0 0 3px rgba(127, 63, 198, 0.1)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';"></textarea>
                
                <p id="undropRemarksError" style="
                    color: #dc2626;
                    font-size: 0.85em;
                    margin: 8px 0 0;
                    display: none;
                    font-weight: 500;
                ">⚠️ Please enter remarks before proceeding.</p>
            </div>
        </div>

        <div style="
            padding: 20px 32px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            background: #f9fafb;
            border-radius: 0 0 14px 14px;
        ">
            <button id="undropModalCancel" style="
                padding: 10px 24px;
                border: 2px solid #d1d5db;
                background: white;
                color: #374151;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.95em;
                transition: all 0.2s;
            " onmouseover="this.style.background='#f3f4f6'; this.style.borderColor='#9ca3af';" onmouseout="this.style.background='white'; this.style.borderColor='#d1d5db';">Cancel</button>
            
            <button id="undropModalConfirm" style="
                padding: 10px 28px;
                border: none;
                background: linear-gradient(135deg, #7f3fc6 0%, #a78bfa 100%);
                color: white;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.95em;
                transition: all 0.3s;
                box-shadow: 0 4px 12px rgba(127, 63, 198, 0.3);
" onmouseover="this.style.boxShadow='0 6px 16px rgba(127, 63, 198, 0.4)'; this.style.transform='translateY(-1px)'" onmouseout="this.style.boxShadow='0 4px 12px rgba(127, 63, 198, 0.3)'; this.style.transform='translateY(0)';">Confirm</button>
        </div>
    `;

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    // Add event listener for clicking outside the modal
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            overlay.remove();
        }
    });

    // Add ESC key support
    const escapeHandler = function(e) {
        if (e.key === 'Escape') {
            overlay.remove();
            document.removeEventListener('keydown', escapeHandler);
        }
    };
    document.addEventListener('keydown', escapeHandler);

    // Style certificate labels on hover and change
    document.querySelectorAll('.undrop-cert-label').forEach(label => {
        const checkbox = label.querySelector('input[type="checkbox"]');
        
        const updateStyle = () => {
            if (checkbox.checked) {
                label.style.background = '#ede9fe';
                label.style.borderColor = '#c4b5fd';
            } else {
                label.style.background = '#f3f4f6';
                label.style.borderColor = 'transparent';
            }
        };

        checkbox.addEventListener('change', updateStyle);
        label.addEventListener('mouseenter', () => {
            if (!checkbox.checked) {
                label.style.background = '#e5e7eb';
            }
        });
        label.addEventListener('mouseleave', () => {
            if (!checkbox.checked) {
                label.style.background = '#f3f4f6';
            }
        });
    });

    document.getElementById('undropModalCancel').onclick = function() {
        overlay.remove();
    };

    document.getElementById('undropModalConfirm').onclick = function() {
        const remarks = document.getElementById('undropRemarks').value.trim();
        const errorEl = document.getElementById('undropRemarksError');
        if (!remarks) {
            errorEl.style.display = 'block';
            document.getElementById('undropRemarks').focus();
            return;
        }
        
        // Collect selected certificates
        const certificates = [];
        const medicalCheck = document.getElementById('undropCert_medical');
        const parentsCheck = document.getElementById('undropCert_parents');
        const otherCheck = document.getElementById('undropCert_other');
        const otherText = document.getElementById('undropOtherText');
        
        if (medicalCheck && medicalCheck.checked) {
            certificates.push('Medical Certificate');
        }
        if (parentsCheck && parentsCheck.checked) {
            certificates.push('Parents Letter');
        }
        if (otherCheck && otherCheck.checked) {
            if (otherText && otherText.value.trim()) {
                certificates.push('Other: ' + otherText.value.trim());
            } else {
                certificates.push('Other');
            }
        }
        
        // Validate that at least one certificate is selected
        if (certificates.length === 0) {
            const certErrorEl = document.getElementById('undropCertificatesError');
            certErrorEl.textContent = '⚠️ Please select at least one reason for undrop.';
            certErrorEl.style.display = 'block';
            return;
        }
        
        // Hide error if certificates are selected
        const certErrorEl = document.getElementById('undropCertificatesError');
        if (certErrorEl) {
            certErrorEl.style.display = 'none';
        }
        
        const certificatesStr = certificates.join(', ');
        
        // Set the remarks and certificates in the hidden form and submit
        const form = document.getElementById('undropForm' + dropId);
        if (form) {
            const remarksInput = document.createElement('input');
            remarksInput.type = 'hidden';
            remarksInput.name = 'undrop_remarks';
            remarksInput.value = remarks;
            form.appendChild(remarksInput);
            
            const certificatesInput = document.createElement('input');
            certificatesInput.type = 'hidden';
            certificatesInput.name = 'undrop_certificates';
            certificatesInput.value = certificatesStr;
            form.appendChild(certificatesInput);
            
            form.submit();
        }
        overlay.remove();
    };

    document.getElementById('undropRemarks').addEventListener('input', function() {
        document.getElementById('undropRemarksError').style.display = 'none';
    });

    // Add event listeners to clear certificate error when any checkbox changes
    const checkboxes = document.querySelectorAll('input[name="undrop_certificates"]');
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const certErrorEl = document.getElementById('undropCertificatesError');
            if (certErrorEl) {
                certErrorEl.style.display = 'none';
            }
        });
    });
}

// Helper function to toggle the "Other" text field
function toggleOtherField() {
    const otherCheck = document.getElementById('undropCert_other');
    const otherText = document.getElementById('undropOtherText');
    if (otherCheck && otherText) {
        otherText.style.display = otherCheck.checked ? 'block' : 'none';
    }

    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.remove();
    });

    // Focus textarea
    setTimeout(function() {
        document.getElementById('undropRemarks').focus();
    }, 100);
}

// Confirm delete action
function confirmDelete(e) {
    if (e && e.preventDefault) e.preventDefault();
    const form = e && e.target ? e.target.closest('form') : null;
    showConfirmModal('Are you sure you want to delete this record?', function() {
        if (form) form.submit();
    });
    return false;
}

// Show notification message
function showNotification(type, message) {
    const div = document.createElement('div');
    div.className = `alert alert-${type}`;
    div.textContent = message;
    
    const container = document.querySelector('.content-wrapper');
    if (container) {
        container.insertBefore(div, container.firstChild);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            div.remove();
        }, 5000);
    }
}

// Format date function (Philippines, 12-hour)
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-PH', {
        timeZone: 'Asia/Manila',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Add any initialization code here
    console.log('Page loaded');
});

// Export current page as table to CSV
function exportTableToCSV(filename = 'export.csv') {
    const table = document.querySelector('table');
    if (!table) return;
    
    let csv = [];
    let rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        let csvRow = [];
        let cells = row.querySelectorAll('td, th');
        
        cells.forEach(cell => {
            csvRow.push('"' + cell.innerText + '"');
        });
        
        csv.push(csvRow.join(','));
    });
    
    downloadCSV(csv.join('\n'), filename);
}

function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.href = URL.createObjectURL(csvFile);
    downloadLink.download = filename;
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Print page function
function printPage() {
    window.print();
}
// Approve class card drop request
function approveDrop(dropId) {
    showConfirmModal('Are you sure you want to approve this class card drop?', function() {
        const form = new FormData();
        form.append('action', 'approve_drop');
        form.append('drop_id', dropId);

        fetch('/CLASS_CARD_DROPPING_SYSTEM/backend/includes/api.php', {
            method: 'POST',
            body: form
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessNotification(data.message);
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showErrorNotification(data.message || 'Error approving drop');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorNotification('Error approving drop: ' + error.message);
        });
    });
}

// Show approval modal
function showApprovalModal(dropId, studentName, subjectName) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.id = 'approvalModal';
    
    const content = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Approve Class Card Drop</h3>
                <button type="button" class="modal-close" onclick="closeApprovalModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p><strong>Student:</strong> ${studentName}</p>
                <p><strong>Subject:</strong> ${subjectName}</p>
                <p>Are you sure you want to approve this class card drop request?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeApprovalModal()">Cancel</button>
                <button type="button" class="btn btn-success" onclick="confirmApproveDrop(${dropId})">Approve</button>
            </div>
        </div>
    `;
    
    modal.innerHTML = content;
    document.body.appendChild(modal);
    modal.style.display = 'block';
}

// Close approval modal
function closeApprovalModal() {
    const modal = document.getElementById('approvalModal');
    if (modal) {
        modal.remove();
    }
}

// Confirm approve drop
function confirmApproveDrop(dropId) {
    approveDrop(dropId);
    closeApprovalModal();
}

// Show success notification banner
function showSuccessNotification(message) {
    const contentWrapper = document.querySelector('.content-wrapper');
    if (!contentWrapper) {
        console.error('Content wrapper not found');
        return;
    }

    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success';
    alertDiv.style.cssText = 'animation: slideDown 0.3s ease-out;';
    alertDiv.innerHTML = `
        <svg style="width: 20px; height: 20px; margin-right: 12px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>${message}</span>
    `;

    contentWrapper.insertBefore(alertDiv, contentWrapper.firstChild);

    setTimeout(() => {
        alertDiv.style.animation = 'slideUp 0.3s ease-out';
        setTimeout(() => alertDiv.remove(), 300);
    }, 4000);
}

// Show error notification banner
function showErrorNotification(message) {
    const contentWrapper = document.querySelector('.content-wrapper');
    if (!contentWrapper) {
        console.error('Content wrapper not found');
        return;
    }

    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-error';
    alertDiv.style.cssText = 'animation: slideDown 0.3s ease-out;';
    alertDiv.innerHTML = `
        <svg style="width: 20px; height: 20px; margin-right: 12px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 0v2m0-2v-2m0 0v-2m0 2h2m-2 0h-2"></path>
        </svg>
        <span>${message}</span>
    `;

    contentWrapper.insertBefore(alertDiv, contentWrapper.firstChild);

    setTimeout(() => {
        alertDiv.style.animation = 'slideUp 0.3s ease-out';
        setTimeout(() => alertDiv.remove(), 300);
    }, 4000);
}