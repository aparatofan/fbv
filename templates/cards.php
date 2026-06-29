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
		</div>
	</div>

	<div class="fbv-tags-section">
		<button type="button" class="fbv-tags-toggle" id="fbv-tags-toggle" aria-expanded="false" aria-controls="fbv-tags">
			<span class="fbv-tags-toggle-label"><?php esc_html_e( 'Tagi', 'fbv' ); ?></span>
			<span class="fbv-tags-toggle-arrow" aria-hidden="true">▾</span>
		</button>
		<div class="fbv-tags" id="fbv-tags" role="group" aria-label="<?php esc_attr_e( 'Filtruj według tagów', 'fbv' ); ?>" hidden></div>
	</div>

	<div class="fbv-cards" id="fbv-cards"></div>

	<p class="fbv-empty" id="fbv-empty" hidden></p>

	<noscript>
		<p class="fbv-noscript"><?php esc_html_e( 'JavaScript jest wymagany, aby wyświetlić kolekcję wersetów.', 'fbv' ); ?></p>
	</noscript>

	<?php if ( $is_admin ) : ?>
		<div class="fbv-admin-buttons">
			<button type="button" class="fbv-import-btn" id="fbv-import-btn"><?php esc_html_e( 'Import CSV', 'fbv' ); ?></button>
			<button type="button" class="fbv-add-btn" id="fbv-add-btn">+ <?php esc_html_e( 'Dodaj werset', 'fbv' ); ?></button>
		</div>

		<div class="fbv-modal-overlay" id="fbv-import-modal" hidden>
			<div class="fbv-modal" role="dialog" aria-modal="true" aria-labelledby="fbv-import-title">
				<h2 class="fbv-modal-title" id="fbv-import-title"><?php esc_html_e( 'Import CSV', 'fbv' ); ?></h2>

				<p class="fbv-import-help">
					<?php esc_html_e( 'Kolumny: reference, text_pl, text_en (opcjonalnie), tags. Pierwszy wiersz może być nagłówkiem.', 'fbv' ); ?>
				</p>

				<label class="fbv-field">
					<span class="fbv-label"><?php esc_html_e( 'Wybierz plik CSV', 'fbv' ); ?></span>
					<input type="file" id="fbv-import-file" accept=".csv,text/csv" />
				</label>

				<label class="fbv-field">
					<span class="fbv-label"><?php esc_html_e( '…lub wklej zawartość CSV', 'fbv' ); ?></span>
					<textarea id="fbv-import-text" rows="8" placeholder="reference,text_pl,text_en,tags"></textarea>
				</label>

				<label class="fbv-checkbox">
					<input type="checkbox" id="fbv-import-skip" checked />
					<span><?php esc_html_e( 'Pomiń wersety, które już istnieją', 'fbv' ); ?></span>
				</label>

				<p class="fbv-import-status" id="fbv-import-status" aria-live="polite"></p>

				<div class="fbv-modal-actions">
					<button type="button" class="fbv-btn fbv-btn-secondary" id="fbv-import-cancel"><?php esc_html_e( 'Anuluj', 'fbv' ); ?></button>
					<button type="button" class="fbv-btn fbv-btn-primary" id="fbv-import-run"><?php esc_html_e( 'Importuj', 'fbv' ); ?></button>
				</div>
			</div>
		</div>

		<div class="fbv-modal-overlay" id="fbv-modal" hidden>
			<div class="fbv-modal" role="dialog" aria-modal="true" aria-labelledby="fbv-modal-title">
				<h2 class="fbv-modal-title" id="fbv-modal-title"></h2>

				<form class="fbv-form" id="fbv-form">
					<input type="hidden" id="fbv-verse-id" value="" />

					<label class="fbv-field">
						<span class="fbv-label" data-i18n="reference"></span>
						<input type="text" id="fbv-reference" autocomplete="off" required />
					</label>

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
