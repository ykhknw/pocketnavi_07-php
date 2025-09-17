<!-- Header -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="?lang=<?php echo $lang; ?>">
            <img src="assets/images/landmark.svg" alt="PocketNavi" width="32" height="32" class="me-2">
            <span class="fw-bold">PocketNavi</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="?lang=<?php echo $lang; ?>">
                        <?php echo $lang === 'ja' ? 'ホーム' : 'Home'; ?>
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown">
                        <?php echo $lang === 'ja' ? '日本語' : 'English'; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['lang' => 'ja'])); ?>">
                                日本語
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['lang' => 'en'])); ?>">
                                English
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
