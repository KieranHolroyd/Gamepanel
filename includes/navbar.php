<link rel="stylesheet" href="/nav.css?<?php echo rand(0, 1000000); ?>">
<section class="navSlider">
    <a href="/"><img src="/favicon.ico" alt="AL Logo"></a>
    <div class="spacer"></div>
    <div class="main">
        <?php if (Permissions::init()->hasPermission("VIEW_GENERAL")): ?>
        <a href='<?php echo $url; ?>'><i class="fas fa-home"></i> <span>Dashboard</span></a>
        <a href='<?php echo $url; ?>logger'><i class="fas fa-clipboard"></i> <span>Log Case</span></a>
        <a id="notificationsBtn" onclick="openOverlay('#notifications')"><i class="fas fa-bell"></i> <span>Notifications</span></a>
            <div class="dropdown-anchor"><i class="fas fa-toolbox"></i> <span>Tools <i class="fas fa-caret-right"></i></span>
                <div class="dropdown">
                    <a href='<?php echo $url; ?>policies'><i class="fas fa-book"></i> <span>Staff Policies</span></a>
                    <a href='<?php echo $url; ?>meetings'><i class="far fa-calendar-alt"></i> <span>Staff Meetings</span></a>
                    <a href='<?php echo $url; ?>notebook'><i class="fas fa-book-open"></i> <span>Notebook</span></a>
                    <?php if (Permissions::init()->hasPermission("VIEW_GAME_PLAYER") && Config::$enableGamePanel): ?>
                        <a href='<?php echo $url; ?>game'><i class="fas fa-server"></i> <span>Game Panel</span></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php if (Permissions::init()->hasPermission("VIEW_SLT")): ?>
            <div class="dropdown-anchor"><i class="fas fa-briefcase"></i> <span>Cases <i class="fas fa-caret-right"></i></span>
                <div class="dropdown">
                    <a href='<?php echo $url; ?>viewer'><i class="fas fa-eye"></i> <span>View</span></a>
                    <a href='<?php echo $url; ?>search?type=cases'><i class="fas fa-search"></i> <span>Search</span></a>
                </div>
            </div>
            <div class="dropdown-anchor"><i class="fas fa-users"></i> <span>Staff <i class="fas fa-caret-right"></i></span>
                <div class="dropdown">
                    <a href='<?php echo $url; ?>staff/'><i class="fas fa-clipboard-list"></i> <span>Manage</span></a>
                    <a href='<?php echo $url; ?>staff/overview'><i class="fas fa-info-circle"></i> <span>Overview</span></a>
                    <a href='<?php echo $url; ?>staff/roles'><i class="fas fa-tag"></i> <span>Role Permissions</span></a>
                    <a href='<?php echo $url; ?>staff/interviews'><i class="fas fa-microphone"></i> <span>Interviews</span></a>
                    <a href='<?php echo $url; ?>staff/audit'><i class="fas fa-list-alt"></i> <span>Audit Log</span></a>
                </div>
            </div>
        <?php endif; ?>
            <div class="dropdown-anchor"><i class="fas fa-sign-out-alt"></i> <span>Logout <i class="fas fa-caret-right"></i></span>
                <div class="dropdown">
                    <a onclick="logout()"><i class="fas fa-sign-out-alt"></i> <span>Yes, Logout</span></a>
                </div>
            </div>
        <div style="height: 58px;"></div>
        <a class="bottom" href="<?php echo $url; ?>me">
            <div class="spacer"></div>
            <i class="fas fa-user"></i> <span><?= $user->displayName(); ?></span>
        </a>
        <?php endif; ?>
    </div>
</section>
<div class="modal" id="logout">
    <button id="close">×</button>
    <div class="content" style="max-width: 400px;border-radius: 5px;padding: 25px;">
        <h1>Are you sure?</h1>
        <div class="btnGroup">
            <button style="border-bottom-right-radius: 0;border-top-right-radius: 0;" onclick="closeAllModal();">No, Cancel</button>
            <button style="border-bottom-left-radius: 0;border-top-left-radius: 0;" onclick="logout();">Yes, Logout</button>
        </div>
    </div>
</div>