<div class="topbar">

    <div class="welcome">

        <h2>
            Welcome,
            <?php echo htmlspecialchars($_SESSION['fullname']); ?> 
        </h2>

    </div>

    <div class="top-right">
        <div class="profile">

            <div class="avatar">

                <?php
                echo strtoupper(substr($_SESSION['fullname'],0,1));
                ?>

            </div>

        </div>

    </div>

</div>