<nav class="nav">
    <i class="fa-solid fa-bars navOpenBtn"></i>

    <a href="#" class="logo">
        <img src="../assets/logo/bms.png" alt="BMS Logo" class="logo-img" />
        <span>BMS</span>
    </a>

    <ul class="nav-links">
        <i class="fa-solid fa-xmark navCloseBtn"></i>
        <li><a href="#">Home</a></li>
        <li><a href="#">Certificates</a></li>
        <li><a href="#">Waste Management</a></li>
        <li><a href="#">Notifications</a></li>
    </ul>

    <div class="nav-right">
        <i class="fa-solid fa-magnifying-glass search-icon" id="searchIcon"></i>

        <div class="profile-container">
            <div class="profile-trigger">
                <img src="../assets/images/admin.png" alt="Profile" />
            </div>

            <div class="profile-dropdown">
                <a href="#">
                    <i class="fa-solid fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="#" data-logout>
                    <i class="fa-solid fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <div class="search-box">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" placeholder="Search here..." />
    </div>
</nav>

<script src="../js/navbar.js"></script>
<script>
    document.querySelector('[data-logout]').addEventListener('click', function (event) {
        event.preventDefault();

        Swal.fire({
            title: 'Are you sure?',
            text: "You will be logged out!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Confirm',
            didOpen: () => {
                document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                document.body.classList.remove("swal2-shown", "swal2-height-auto");
            },
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "../components/logout.php";
            }
        });
    });
</script>