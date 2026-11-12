            <div class="footer-section footer-links">
                <a href="https://www.wordofgodteam.com/" target="_blank" class="footer-link">
                    <i class="bi bi-people me-1"></i>About Us
                </a>
                <span class="footer-separator">|</span>
                <a href="https://wordofgod.in/bible-devotions/" target="_blank" class="footer-link">
                    <i class="bi bi-journal-bookmark me-1"></i>Bible Devotions
                </a>
                <span class="footer-separator">|</span>
                <a href="https://wordofgod.in/bibles/" target="_blank" class="footer-link">
                    <i class="bi bi-book-half me-1"></i>Online Bibles
                </a>
                <span class="footer-separator">|</span>
                <a href="https://wordofgod.in/good-news-collections/" target="_blank" class="footer-link">
                    <i class="bi bi-box-seam me-1"></i>Good News Collections
                </a>
                <span class="footer-separator">|</span>
                <a href="https://wordofgod.in/bibledictionary/" target="_blank" class="footer-link">
                    <i class="bi bi-collection me-1"></i>Bible Dictionaries
                </a>
                <span class="footer-separator">|</span>
                <a href="https://wordofgod.in/bible-concordance/" target="_blank" class="footer-link">
                    <i class="bi bi-search me-1"></i>Bible Concordances
                </a>
                <span class="footer-separator">|</span>
                <a href="https://wordofgod.in/bible-wallpapers/" target="_blank" class="footer-link">
                    <i class="bi bi-card-image me-1"></i>Bible Wallpapers
                </a>
                <span class="footer-separator">|</span>
                <a href="https://wordofgod.in/bible-app-modules/" target="_blank" class="footer-link">
                    <i class="bi bi-phone me-1"></i>Bible App Modules
                </a>
                <span class="footer-separator">|</span>
                <a href="https://wordofgod.in/" target="_blank" class="footer-link">
                    <i class="bi bi-gift me-1"></i>Free Christian Resources
                </a>
                <span class="footer-separator">|</span>
                <span class="footer-visitors">
                    <i class="bi bi-emoji-heart-eyes me-1"></i><?= htmlspecialchars(btT('ft.visitors')) ?>: <?= number_format((int) ($visitors2 ?? 0)) ?>
                </span>
                <span class="footer-separator">|</span>
                <span class="footer-visitors">
                    <i class="bi bi-download me-1"></i><?= htmlspecialchars(btT('ft.downloads')) ?>: <?= number_format((int) ($downloads2 ?? 0)) ?>
                </span>
                <span class="footer-separator">|</span>
                <span class="footer-visitors">
                    <i class="bi bi-calendar2-check me-1"></i><?= htmlspecialchars(btT('ft.plansCreated')) ?>: <?= number_format((int) ($plansCreated2 ?? 0)) ?>
                </span>
                <span class="footer-separator">|</span>
                <span class="footer-visitors">
                    <i class="bi bi-book me-1"></i><?= htmlspecialchars(btT('ft.booksRead')) ?>: <?= number_format((int) ($booksRead2 ?? 0)) ?>
                </span>
                <span class="footer-separator">|</span>
                <span class="footer-visitors">
                    <i class="bi bi-journal-check me-1"></i><?= htmlspecialchars(btT('ft.chaptersRead')) ?>: <?= number_format((int) ($chaptersRead2 ?? 0)) ?>
                </span>
                <span class="footer-separator">|</span>
                <span class="footer-visitors">
                    <i class="bi bi-patch-question me-1"></i><?= htmlspecialchars(btT('ft.quizzes')) ?>: <?= number_format((int) ($quizzes2 ?? 0)) ?>
                </span>
                <?php if (!empty($topLanguage2)): ?>
                    <span class="footer-separator">|</span>
                    <span class="footer-visitors"><i class="bi bi-translate me-1"></i><?= htmlspecialchars(btT('ft.topLanguage')) ?>: <?= htmlspecialchars($topLanguage2) ?></span>
                <?php endif; ?>
                <?php if (!empty($topBooks2)): ?>
                    <span class="footer-separator">|</span>
                    <span class="footer-visitors"><i class="bi bi-bookmark-star me-1"></i><?= htmlspecialchars(btT('ft.top3Books')) ?>: <?= htmlspecialchars(implode(', ', $topBooks2)) ?></span>
                <?php endif; ?>
                <?php if (!empty($topScope2)): ?>
                    <span class="footer-separator">|</span>
                    <span class="footer-visitors"><i class="bi bi-bullseye me-1"></i><?= htmlspecialchars(btT('ft.topScope')) ?>: <?= htmlspecialchars(btT('scope.' . $topScope2)) ?></span>
                <?php endif; ?>
                <?php if (!empty($topMode2)): ?>
                    <span class="footer-separator">|</span>
                    <span class="footer-visitors"><i class="bi bi-list-check me-1"></i><?= htmlspecialchars(btT('ft.topReading')) ?>: <?= htmlspecialchars(btT('mode.' . $topMode2)) ?></span>
                <?php endif; ?>
            </div>

            <div style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; opacity: 0; pointer-events: none;" aria-hidden="true">
                <a href="bot.php" tabindex="-1">.</a>
            </div>