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
                <button class="btn btn-danger" onclick="window.location.href='/CLASS_CARD_DROPPING_SYSTEM/includes/logout.php'">Yes, Logout</button>
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
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999;';

    const box = document.createElement('div');
    box.style.cssText = 'background:#fff;border-radius:8px;padding:0;min-width:350px;max-width:450px;box-shadow:0 4px 20px rgba(0,0,0,0.3);';

    box.innerHTML = `
        <div style="padding:20px 20px 10px;border-bottom:1px solid #eee;">
            <h3 style="margin:0;font-size:1.1em;">Confirm Action</h3>
        </div>
        <div style="padding:20px;">
            <p style="margin:0;color:#555;">${message}</p>
        </div>
        <div style="padding:12px 20px;border-top:1px solid #eee;display:flex;gap:10px;justify-content:flex-end;">
            <button id="confirmModalCancel" style="padding:8px 18px;border:1px solid #ccc;background:#f5f5f5;border-radius:5px;cursor:pointer;font-size:0.95em;">Cancel</button>
            <button id="confirmModalOk" style="padding:8px 18px;border:none;background:#dc3545;color:#fff;border-radius:5px;cursor:pointer;font-size:0.95em;">Confirm</button>
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
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.remove();
    });
}

// Undrop modal with remarks textarea and certificate checkboxes
function showUndropModal(dropId) {
    const existing = document.getElementById('undropModal');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'undropModal';
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999;';

    const box = document.createElement('div');
    box.style.cssText = 'background:#fff;border-radius:8px;padding:0;min-width:400px;max-width:550px;box-shadow:0 4px 20px rgba(0,0,0,0.3);';

    box.innerHTML = `
        <div style="padding:20px 20px 10px;border-bottom:1px solid #eee;">
            <h3 style="margin:0;font-size:1.1em;">Undrop Class Card</h3>
        </div>
        <div style="padding:20px;">
            <p style="margin:0 0 15px;color:#555;">Are you sure you want to undrop this class card?</p>
            
            <label style="display:block;margin-bottom:10px;font-weight:600;color:#333;font-size:0.95em;">Reason for Undrop</label>
            <div style="background:#f9f9f9;border:1px solid #e0e0e0;border-radius:5px;padding:10px;margin-bottom:15px;">
                <div style="margin-bottom:8px;">
                    <input type="checkbox" id="undropCert_medical" name="undrop_certificates" value="Medical Certificate" style="margin-right:8px;">
                    <label for="undropCert_medical" style="display:inline;cursor:pointer;font-weight:400;">Medical Certificate</label>
                </div>
                <div style="margin-bottom:8px;">
                    <input type="checkbox" id="undropCert_parents" name="undrop_certificates" value="Parents Letter" style="margin-right:8px;">
                    <label for="undropCert_parents" style="display:inline;cursor:pointer;font-weight:400;">Parents Letter</label>
                </div>
                <div>
                    <input type="checkbox" id="undropCert_other" name="undrop_certificates" value="Other" style="margin-right:8px;" onchange="toggleOtherField()">
                    <label for="undropCert_other" style="display:inline;cursor:pointer;font-weight:400;">Other</label>
                </div>
                <input type="text" id="undropOtherText" placeholder="Please specify..." style="display:none;width:100%;padding:8px;margin-top:8px;border:1px solid #ccc;border-radius:4px;font-size:0.9em;box-sizing:border-box;">
            </div>
            
            <label for="undropRemarks" style="display:block;margin-bottom:6px;font-weight:600;color:#333;font-size:0.95em;">Admin Remarks <span style="color:#999;font-weight:400;">(required)</span></label>
            <textarea id="undropRemarks" rows="4" placeholder="Enter reason for undropping..." style="width:100%;padding:10px;border:1px solid #ccc;border-radius:5px;font-size:0.95em;resize:vertical;box-sizing:border-box;"></textarea>
            <p id="undropRemarksError" style="color:#dc3545;font-size:0.85em;margin:4px 0 0;display:none;">Please enter remarks before proceeding.</p>
        </div>
        <div style="padding:12px 20px;border-top:1px solid #eee;display:flex;gap:10px;justify-content:flex-end;">
            <button id="undropModalCancel" style="padding:8px 18px;border:1px solid #ccc;background:#f5f5f5;border-radius:5px;cursor:pointer;font-size:0.95em;">Cancel</button>
            <button id="undropModalConfirm" style="padding:8px 18px;border:none;background:#dc3545;color:#fff;border-radius:5px;cursor:pointer;font-size:0.95em;">Confirm Undrop</button>
        </div>
    `;

    overlay.appendChild(box);
    document.body.appendChild(overlay);

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
            // Show error for certificates
            let certErrorEl = document.getElementById('undropCertificatesError');
            if (!certErrorEl) {
                certErrorEl = document.createElement('p');
                certErrorEl.id = 'undropCertificatesError';
                certErrorEl.style.cssText = 'color:#dc3545;font-size:0.85em;margin:8px 0 0;';
                const certBox = document.querySelector('[style*="background:#f9f9f9"]');
                if (certBox) {
                    certBox.parentNode.insertBefore(certErrorEl, certBox.nextSibling);
                }
            }
            certErrorEl.textContent = 'Please select at least one reason for undrop.';
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
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/CLASS_CARD_DROPPING_SYSTEM/includes/api.php?action=approve_drop';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'drop_id';
        input.value = dropId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
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