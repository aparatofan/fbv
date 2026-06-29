<?php
/**
 * Frontend interface shell.
 *
 * Cards are rendered client-side by assets/js/fbv-frontend.js from the
 * localized FBV_DATA object; this template provides the static structure,
 * the admin modal and the no-JS fallback message.
 *
 * @package FBV
 *
 * @var bool $is_admin Whether the current user can manage verses.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="fbv-app" data-lang="pl"<?php echo $is_admin ? ' data-admin="1"' : ''; ?>>

	<div class="fbv-toolbar">
		<div class="fbv-search-wrap">
			<input type="search" class="fbv-search" id="fbv-search"
				placeholder="<?php esc_attr_e( 'Szukaj wersetów…', 'fbv' ); ?>"
				aria-label="<?php esc_attr_e( 'Szukaj wersetów', 'fbv' ); ?>" />
		</div>

		<div class="fbv-toolbar-right">
			<span class="fbv-count" id="fbv-count" aria-live="polite"></span>
			<div class="fbv-lang-toggle" role="group" aria-label="<?php esc_attr_e( 'Język', 'fbv' ); ?>">
				<button type="button" class="fbv-lang-btn is-active" data-lang="pl">PL</button>
				<button type="button" class="fbv-lang-btn" data-lang="en">EN</button>
			</div>
		</div>
	</div>

	<div class="fbv-tags" id="fbv-tags" role="group" aria-label="<?php esc_attr_e( 'Filtruj według tagów', 'fbv' ); ?>"></div>

	<div class="fbv-cards" id="fbv-cards"></div>

	<p class="fbv-empty" id="fbv-empty" hidden></p>

	<noscript>
		<p class="fbv-noscript"><?php esc_html_e( 'JavaScript jest wymagany, aby wyświetlić kolekcję wersetów.', 'fbv' ); ?></p>
	</noscript>

	<?php if ( $is_admin ) : ?>
		<button type="button" class="fbv-add-btn" id="fbv-add-btn">+ <?php esc_html_e( 'Dodaj werset', 'fbv' ); ?></button>

		<div class="fbv-modal-overlay" id="fbv-modal" hidden>
			<div class="fbv-modal" role="dialog" aria-modal="true" aria-labelledby="fbv-modal-title">
				<h2 class="fbv-modal-title" id="fbv-modal-title"></h2>

				<form class="fbv-form" id="fbv-form">
					<input type="hidden" id="fbv-verse-id" value="" />

					<label class="fbv-field">
						<span class="fbv-label" data-i18n="reference"></span>
						<span class="fbv-input-row">
							<input type="text" id="fbv-reference" autocomplete="off" required />
							<span class="fbv-spinner" id="fbv-spinner" hidden></span>
						</span>
					</label>

					<p class="fbv-fetch-status" id="fbv-fetch-status" aria-live="polite"></p>

					<label class="fbv-field">
						<span class="fbv-label" data-i18n="textPl"></span>
						<textarea id="fbv-text-pl" rows="4"></textarea>
					</label>

					<label class="fbv-field">
						<span class="fbv-label" data-i18n="textEn"></span>
						<textarea id="fbv-text-en" rows="4"></textarea>
					</label>

					<label class="fbv-field">
						<span class="fbv-label" data-i18n="tags"></span>
						<input type="text" id="fbv-tags-input" autocomplete="off" list="fbv-tags-list" />
						<datalist id="fbv-tags-list"></datalist>
					</label>

					<div class="fbv-jw-links">
						<a href="#" id="fbv-link-pl" target="_blank" rel="noopener noreferrer" data-i18n="openPl"></a>
						<a href="#" id="fbv-link-en" target="_blank" rel="noopener noreferrer" data-i18n="openEn"></a>
					</div>

					<p class="fbv-form-error" id="fbv-form-error" role="alert"></p>

					<div class="fbv-modal-actions">
						<button type="button" class="fbv-btn fbv-btn-secondary" id="fbv-cancel" data-i18n="cancel"></button>
						<button type="submit" class="fbv-btn fbv-btn-primary" id="fbv-save" data-i18n="save"></button>
					</div>
				</form>
			</div>
		</div>
	<?php endif; ?>
</div>
