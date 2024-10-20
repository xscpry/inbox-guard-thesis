<header>
<nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
      <div class="container-fluid">
        <a href='/' class="navbar-brand"><img src="../img/inboxguard logo.png" class="logo" href="./index.php"></a>
        <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
          <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
            <li class="nav-item mx-3">
                <a class="nav-link <?= $homepage ?>" aria-current="page" href="/dashboard.php">Dashboard</a>
            </li>
            <li class="nav-item mx-3">
              <a class="nav-link <?= $aboutuspage ?>" aria-current="page" href="/about-us.php">About Us</a>
            </li>
            <li class="nav-item mx-3">
              <a class="nav-link <?= $logout ?>" aria-current="page" href="logout.php">Logout</a>
            </li>
          </ul>
        </div>
      </div>
  </nav>
</header>
