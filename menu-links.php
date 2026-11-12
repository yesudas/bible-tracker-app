<nav class="site-nav" id="siteNav" aria-label="WordOfGod.in resources" hidden>
    <ul class="site-nav-list">
        <?php if (!empty($user) && function_exists('btIsAdmin') && btIsAdmin($user)): ?>
        <li><a href="a.php"><i class="bi bi-shield-lock" aria-hidden="true"></i> Admin</a></li>
        <?php endif; ?>
        <li class="site-nav-install"><button type="button" class="install-app-btn" hidden><i class="bi bi-box-arrow-in-down" aria-hidden="true"></i> <?= htmlspecialchars(btT('nav.installApp')) ?></button></li>
        <li><a href="https://www.wordofgodteam.com/" target="_blank" rel="noopener noreferrer"><i class="bi bi-people" aria-hidden="true"></i> About Us</a></li>
        <li><a href="https://wordofgod.in/bible-devotions/" target="_blank" rel="noopener noreferrer"><i class="bi bi-journal-bookmark" aria-hidden="true"></i> Bible Devotions</a></li>
        <li><a href="https://wordofgod.in/bibles/" target="_blank" rel="noopener noreferrer"><i class="bi bi-book-half" aria-hidden="true"></i> Online Bibles</a></li>
        <li><a href="https://wordofgod.in/good-news-collections/" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-seam" aria-hidden="true"></i> Good News Collections</a></li>
        <li><a href="https://wordofgod.in/bibledictionary/" target="_blank" rel="noopener noreferrer"><i class="bi bi-collection" aria-hidden="true"></i> Bible Dictionaries</a></li>
        <li><a href="https://wordofgod.in/bible-concordance/" target="_blank" rel="noopener noreferrer"><i class="bi bi-search" aria-hidden="true"></i> Bible Concordances</a></li>
        <li><a href="https://wordofgod.in/bible-wallpapers/" target="_blank" rel="noopener noreferrer"><i class="bi bi-card-image" aria-hidden="true"></i> Bible Wallpapers</a></li>
        <li><a href="https://wordofgod.in/bible-app-modules/" target="_blank" rel="noopener noreferrer"><i class="bi bi-phone" aria-hidden="true"></i> Bible App Modules</a></li>
        <li><a href="https://wordofgod.in/" target="_blank" rel="noopener noreferrer"><i class="bi bi-gift" aria-hidden="true"></i> Free Christian Resources</a></li>
        <?php if (!empty($logout)): ?>
        <li><a href="<?= htmlspecialchars($logout) ?>"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> <?= htmlspecialchars(btT('nav.signOut')) ?></a></li>
        <?php endif; ?>
    </ul>
</nav>
