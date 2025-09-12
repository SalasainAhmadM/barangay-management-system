<div class="sidebar">
    <div class="logo-details">
        <img src="../assets/logo/bms.png" alt="BMS Logo" class="logo-img">
        <div class="logo_name">BMS</div>
        <i class="fa-solid fa-bars" id="btn"></i>
    </div>

    <ul class="nav-list">
        <!-- <li>
          <i class="fa-solid fa-magnifying-glass"></i>
         <input type="text" placeholder="Search...">
         <span class="tooltip">Search</span>
      </li> -->
        <li>
            <a href="#">
                <i class="fa-solid fa-table-columns"></i>
                <span class="links_name">Dashboard</span>
            </a>
            <span class="tooltip">Dashboard</span>
        </li>

        <li>
            <a href="#">
                <i class="fa-solid fa-users"></i>
                <span class="links_name">Residents</span>
            </a>
            <span class="tooltip">Residents</span>
        </li>

        <li>
            <a href="#">
                <i class="fa-solid fa-file-lines"></i>
                <span class="links_name">Requests</span>
            </a>
            <span class="tooltip">Requests</span>
        </li>

        <li>
            <a href="#">
                <i class="fa-solid fa-recycle"></i>
                <span class="links_name">Waste Mgmt</span>
            </a>
            <span class="tooltip">Waste Management</span>
        </li>

        <li>
            <a href="#">
                <i class="fa-solid fa-message"></i>
                <span class="links_name">SMS Notify</span>
            </a>
            <span class="tooltip">SMS Notifications</span>
        </li>

        <li>
            <a href="#">
                <i class="fa-solid fa-chart-line"></i>
                <span class="links_name">Reports</span>
            </a>
            <span class="tooltip">Reports</span>
        </li>

        <li>
            <a href="#">
                <i class="fa-solid fa-gear"></i>
                <span class="links_name">Settings</span>
            </a>
            <span class="tooltip">Settings</span>
        </li>

        <li class="profile">
            <div class="profile-details">
                <img src="../assets/images/admin.png" alt="profileImg">
                <div class="name_job">
                    <div class="name">Davy D. Xebec</div>
                    <div class="job">Pirate King</div>
                </div>
            </div>
            <a href="#" data-logout>
                <i class="fa-solid fa-right-from-bracket" id="log_out"></i>
            </a>
        </li>
    </ul>
</div>

<script src="../js/sidebar.js"></script>
<script>
    document.querySelector('[data-logout]').addEventListener('click', function (event) {
        event.preventDefault();

        Swal.fire({
            title: 'Are you sure?',
            text: "You will be logged out!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Confirm'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "../components/logout.php";
            }
        });
    });
</script>