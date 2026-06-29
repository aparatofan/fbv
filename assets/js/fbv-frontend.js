/**
 * FBV — Favourite Bible Verses frontend.
 * Vanilla JS: search, tag filtering, language toggle, and the admin
 * add/edit/delete modal with semi-automatic jw.org fetch.
 */
( function () {
	'use strict';

	if ( typeof window.FBV_DATA === 'undefined' ) {
		return;
	}

	var DATA = window.FBV_DATA;

	var state = {
		lang: DATA.lang === 'en' ? 'en' : 'pl',
		activeTag: null,
		search: '',
		verses: Array.isArray( DATA.verses ) ? DATA.verses.slice() : [],
		tags: Array.isArray( DATA.tags ) ? DATA.tags.slice() : []
	};

	var root = document.querySelector( '.fbv-app' );
	if ( ! root ) {
		return;
	}

	var els = {
		search: root.querySelector( '#fbv-search' ),
		count: root.querySelector( '#fbv-count' ),
		tags: root.querySelector( '#fbv-tags' ),
		cards: root.querySelector( '#fbv-cards' ),
		empty: root.querySelector( '#fbv-empty' ),
		langButtons: root.querySelectorAll( '.fbv-lang-btn' )
	};

	/* Helpers ---------------------------------------------------------- */

	function t( key ) {
		var pack = DATA.i18n[ state.lang ] || {};
		return pack[ key ] || key;
	}

	function normalize( str ) {
		str = ( str || '' ).toString().toLowerCase();
		var map = { 'ą': 'a', 'ć': 'c', 'ę': 'e', 'ł': 'l', 'ń': 'n', 'ó': 'o', 'ś': 's', 'ż': 'z', 'ź': 'z' };
		return str.replace( /[ąćęłńóśżź]/g, function ( c ) {
			return map[ c ] || c;
		} ).replace( /[^a-z0-9]+/g, ' ' ).trim();
	}

	function verseReference( verse ) {
		return state.lang === 'en' && verse.reference_en ? verse.reference_en : verse.reference;
	}

	function verseText( verse ) {
		if ( state.lang === 'en' ) {
			return verse.text_en || verse.text_pl || '';
		}
		return verse.text_pl || verse.text_en || '';
	}

	function pluralizeCount( n ) {
		if ( state.lang === 'en' ) {
			return ( n === 1 ? t( 'versesOne' ) : t( 'versesMany' ) ).replace( '%d', n );
		}
		// Polish pluralization.
		var tmpl;
		var mod10 = n % 10;
		var mod100 = n % 100;
		if ( n === 1 ) {
			tmpl = t( 'versesOne' );
		} else if ( mod10 >= 2 && mod10 <= 4 && ! ( mod100 >= 12 && mod100 <= 14 ) ) {
			tmpl = t( 'versesFew' );
		} else {
			tmpl = t( 'versesMany' );
		}
		return tmpl.replace( '%d', n );
	}

	function el( tag, className, text ) {
		var node = document.createElement( tag );
		if ( className ) {
			node.className = className;
		}
		if ( text !== undefined ) {
			node.textContent = text;
		}
		return node;
	}

	/* Filtering -------------------------------------------------------- */

	function filteredVerses() {
		var needle = normalize( state.search );
		return state.verses.filter( function ( verse ) {
			if ( state.activeTag ) {
				var hasTag = ( verse.tags || [] ).some( function ( tag ) {
					return tag.slug === state.activeTag;
				} );
				if ( ! hasTag ) {
					return false;
				}
			}
			if ( needle ) {
				var hay = normalize( [
					verse.reference,
					verse.reference_en,
					verse.text_pl,
					verse.text_en,
					( verse.tags || [] ).map( function ( x ) { return x.name; } ).join( ' ' )
				].join( ' ' ) );
				if ( hay.indexOf( needle ) === -1 ) {
					return false;
				}
			}
			return true;
		} );
	}

	/* Rendering -------------------------------------------------------- */

	function renderCount( n ) {
		if ( els.count ) {
			els.count.textContent = pluralizeCount( n );
		}
	}

	function renderTags() {
		if ( ! els.tags ) {
			return;
		}
		els.tags.innerHTML = '';
		state.tags.forEach( function ( tag ) {
			var pill = el( 'button', 'fbv-tag-pill' );
			pill.type = 'button';
			pill.dataset.slug = tag.slug;
			pill.appendChild( document.createTextNode( tag.name ) );
			var count = el( 'span', 'fbv-tag-count', '(' + tag.count + ')' );
			pill.appendChild( count );
			if ( state.activeTag === tag.slug ) {
				pill.classList.add( 'is-active' );
			}
			pill.addEventListener( 'click', function () {
				state.activeTag = state.activeTag === tag.slug ? null : tag.slug;
				render();
			} );
			els.tags.appendChild( pill );
		} );
	}

	function renderCard( verse ) {
		var card = el( 'article', 'fbv-card' );
		card.appendChild( el( 'div', 'fbv-card-ref', verseReference( verse ) ) );
		card.appendChild( el( 'p', 'fbv-card-text', verseText( verse ) ) );

		var tagWrap = el( 'div', 'fbv-card-tags' );
		( verse.tags || [] ).forEach( function ( tag ) {
			var btn = el( 'button', 'fbv-card-tag', tag.name );
			btn.type = 'button';
			btn.addEventListener( 'click', function () {
				state.activeTag = state.activeTag === tag.slug ? null : tag.slug;
				render();
				window.scrollTo( { top: 0, behavior: 'smooth' } );
			} );
			tagWrap.appendChild( btn );
		} );
		card.appendChild( tagWrap );

		if ( DATA.isAdmin ) {
			var admin = el( 'div', 'fbv-card-admin' );

			var edit = el( 'button', 'fbv-icon-btn', '✎' );
			edit.type = 'button';
			edit.title = t( 'edit' );
			edit.setAttribute( 'aria-label', t( 'edit' ) );
			edit.addEventListener( 'click', function () {
				openModal( verse );
			} );

			var del = el( 'button', 'fbv-icon-btn', '🗑' );
			del.type = 'button';
			del.title = t( 'delete' );
			del.setAttribute( 'aria-label', t( 'delete' ) );
			del.addEventListener( 'click', function () {
				deleteVerse( verse );
			} );

			admin.appendChild( edit );
			admin.appendChild( del );
			card.appendChild( admin );
		}

		return card;
	}

	function renderCards( list ) {
		els.cards.innerHTML = '';
		list.forEach( function ( verse ) {
			els.cards.appendChild( renderCard( verse ) );
		} );

		if ( els.empty ) {
			if ( list.length === 0 ) {
				els.empty.textContent = t( 'noResults' );
				els.empty.hidden = false;
			} else {
				els.empty.hidden = true;
			}
		}
	}

	function render() {
		var list = filteredVerses();
		renderCount( list.length );
		renderTags();
		renderCards( list );
		updateLangButtons();
	}

	function updateLangButtons() {
		Array.prototype.forEach.call( els.langButtons, function ( btn ) {
			btn.classList.toggle( 'is-active', btn.dataset.lang === state.lang );
		} );
		if ( els.search ) {
			els.search.placeholder = t( 'search' );
		}
	}

	/* Events ----------------------------------------------------------- */

	if ( els.search ) {
		els.search.addEventListener( 'input', function () {
			state.search = els.search.value;
			render();
		} );
	}

	Array.prototype.forEach.call( els.langButtons, function ( btn ) {
		btn.addEventListener( 'click', function () {
			state.lang = btn.dataset.lang === 'en' ? 'en' : 'pl';
			root.setAttribute( 'data-lang', state.lang );
			refreshModalLabels();
			render();
		} );
	} );

	/* Admin: modal ----------------------------------------------------- */

	var modal = DATA.isAdmin ? buildModalRefs() : null;

	function buildModalRefs() {
		return {
			overlay: root.querySelector( '#fbv-modal' ),
			title: root.querySelector( '#fbv-modal-title' ),
			form: root.querySelector( '#fbv-form' ),
			id: root.querySelector( '#fbv-verse-id' ),
			reference: root.querySelector( '#fbv-reference' ),
			textPl: root.querySelector( '#fbv-text-pl' ),
			textEn: root.querySelector( '#fbv-text-en' ),
			tagsInput: root.querySelector( '#fbv-tags-input' ),
			tagsList: root.querySelector( '#fbv-tags-list' ),
			spinner: root.querySelector( '#fbv-spinner' ),
			fetchStatus: root.querySelector( '#fbv-fetch-status' ),
			linkPl: root.querySelector( '#fbv-link-pl' ),
			linkEn: root.querySelector( '#fbv-link-en' ),
			error: root.querySelector( '#fbv-form-error' ),
			save: root.querySelector( '#fbv-save' ),
			cancel: root.querySelector( '#fbv-cancel' ),
			addBtn: root.querySelector( '#fbv-add-btn' )
		};
	}

	function refreshModalLabels() {
		if ( ! modal ) {
			return;
		}
		root.querySelectorAll( '[data-i18n]' ).forEach( function ( node ) {
			node.textContent = t( node.dataset.i18n );
		} );
		if ( modal.addBtn ) {
			modal.addBtn.textContent = t( 'addVerse' );
		}
		if ( modal.reference ) {
			modal.reference.placeholder = t( 'referencePh' );
		}
	}

	function populateTagSuggestions() {
		if ( ! modal || ! modal.tagsList ) {
			return;
		}
		modal.tagsList.innerHTML = '';
		state.tags.forEach( function ( tag ) {
			var opt = document.createElement( 'option' );
			opt.value = tag.name;
			modal.tagsList.appendChild( opt );
		} );
	}

	function openModal( verse ) {
		if ( ! modal ) {
			return;
		}
		modal.form.reset();
		modal.error.textContent = '';
		modal.fetchStatus.textContent = '';
		modal.fetchStatus.className = 'fbv-fetch-status';
		modal.linkPl.style.display = 'none';
		modal.linkEn.style.display = 'none';
		populateTagSuggestions();

		if ( verse ) {
			modal.title.textContent = t( 'editVerse' );
			modal.id.value = verse.id;
			modal.reference.value = verse.reference;
			modal.textPl.value = verse.text_pl || '';
			modal.textEn.value = verse.text_en || '';
			modal.tagsInput.value = ( verse.tags || [] ).map( function ( x ) { return x.name; } ).join( ', ' );
		} else {
			modal.title.textContent = t( 'addVerse' ).replace( /^\+\s*/, '' );
			modal.id.value = '';
		}

		modal.overlay.hidden = false;
		modal.reference.focus();
	}

	function closeModal() {
		if ( modal ) {
			modal.overlay.hidden = true;
		}
	}

	function setLinks( urlPl, urlEn ) {
		if ( urlPl ) {
			modal.linkPl.href = urlPl;
			modal.linkPl.style.display = '';
		}
		if ( urlEn ) {
			modal.linkEn.href = urlEn;
			modal.linkEn.style.display = '';
		}
	}

	function fetchVerse() {
		var reference = modal.reference.value.trim();
		if ( ! reference ) {
			return;
		}
		modal.spinner.hidden = false;
		modal.fetchStatus.className = 'fbv-fetch-status';
		modal.fetchStatus.textContent = t( 'fetching' );

		apiRequest( 'POST', '/fetch-verse', { reference: reference } )
			.then( function ( res ) {
				modal.spinner.hidden = true;
				setLinks( res.url_pl, res.url_en );

				if ( res.text_pl && ! modal.textPl.value.trim() ) {
					modal.textPl.value = res.text_pl;
				}
				if ( res.text_en && ! modal.textEn.value.trim() ) {
					modal.textEn.value = res.text_en;
				}

				if ( res.success && ( res.text_pl || res.text_en ) ) {
					var parts = [];
					parts.push( 'PL ' + ( res.text_pl ? '✓' : '✗' ) );
					parts.push( 'EN ' + ( res.text_en ? '✓' : '✗' ) );
					modal.fetchStatus.className = 'fbv-fetch-status is-ok';
					modal.fetchStatus.textContent = parts.join( '   ' );
				} else {
					modal.fetchStatus.className = 'fbv-fetch-status is-error';
					modal.fetchStatus.textContent = ( res.errors && res.errors.length ) ? res.errors.join( ' ' ) : t( 'fetchFailed' );
				}
			} )
			.catch( function ( err ) {
				modal.spinner.hidden = true;
				modal.fetchStatus.className = 'fbv-fetch-status is-error';
				modal.fetchStatus.textContent = ( err && err.message ) ? err.message : t( 'fetchFailed' );
			} );
	}

	function saveVerse( e ) {
		e.preventDefault();
		modal.error.textContent = '';

		var reference = modal.reference.value.trim();
		var textPl = modal.textPl.value.trim();
		var textEn = modal.textEn.value.trim();

		if ( ! reference || ( ! textPl && ! textEn ) ) {
			modal.error.textContent = t( 'required' );
			return;
		}

		// Duplicate warning (only for new verses).
		if ( ! modal.id.value ) {
			var dupe = state.verses.some( function ( v ) {
				return normalize( v.reference ) === normalize( reference );
			} );
			if ( dupe && ! window.confirm( t( 'duplicate' ) + '\n\n' + reference ) ) {
				return;
			}
		}

		var payload = {
			reference: reference,
			text_pl: textPl,
			text_en: textEn,
			tags: modal.tagsInput.value
		};

		modal.save.disabled = true;
		var id = modal.id.value;
		var method = id ? 'PUT' : 'POST';
		var path = id ? '/verses/' + id : '/verses';

		apiRequest( method, path, payload )
			.then( function ( saved ) {
				upsertVerse( saved );
				return refreshTags();
			} )
			.then( function () {
				modal.save.disabled = false;
				closeModal();
				render();
			} )
			.catch( function ( err ) {
				modal.save.disabled = false;
				modal.error.textContent = ( err && err.message ) ? err.message : t( 'saveError' );
			} );
	}

	function deleteVerse( verse ) {
		if ( ! window.confirm( t( 'confirmDelete' ) + '\n\n' + verse.reference ) ) {
			return;
		}
		apiRequest( 'DELETE', '/verses/' + verse.id, null )
			.then( function () {
				state.verses = state.verses.filter( function ( v ) {
					return v.id !== verse.id;
				} );
				return refreshTags();
			} )
			.then( function () {
				render();
			} )
			.catch( function ( err ) {
				window.alert( ( err && err.message ) ? err.message : t( 'saveError' ) );
			} );
	}

	function upsertVerse( saved ) {
		var idx = -1;
		state.verses.forEach( function ( v, i ) {
			if ( v.id === saved.id ) {
				idx = i;
			}
		} );
		if ( idx >= 0 ) {
			state.verses[ idx ] = saved;
		} else {
			state.verses.push( saved );
		}
		// Keep canonical order: book, chapter, verse_start.
		state.verses.sort( function ( a, b ) {
			return ( a.book_number - b.book_number ) ||
				( a.chapter - b.chapter ) ||
				( a.verse_start - b.verse_start );
		} );
	}

	function refreshTags() {
		return apiRequest( 'GET', '/tags', null )
			.then( function ( tags ) {
				if ( Array.isArray( tags ) ) {
					state.tags = tags;
					if ( state.activeTag && ! tags.some( function ( x ) { return x.slug === state.activeTag; } ) ) {
						state.activeTag = null;
					}
				}
			} )
			.catch( function () { /* non-fatal */ } );
	}

	/* API -------------------------------------------------------------- */

	function apiRequest( method, path, body ) {
		var opts = {
			method: method,
			headers: { 'X-WP-Nonce': DATA.nonce },
			credentials: 'same-origin'
		};
		if ( body !== null && body !== undefined ) {
			opts.headers[ 'Content-Type' ] = 'application/json';
			opts.body = JSON.stringify( body );
		}
		return fetch( DATA.restUrl + path, opts ).then( function ( res ) {
			return res.json().catch( function () { return {}; } ).then( function ( data ) {
				if ( ! res.ok ) {
					var msg = data && data.message ? data.message : ( 'HTTP ' + res.status );
					throw new Error( msg );
				}
				return data;
			} );
		} );
	}

	/* Wire admin events ------------------------------------------------ */

	if ( modal ) {
		if ( modal.addBtn ) {
			modal.addBtn.addEventListener( 'click', function () { openModal( null ); } );
		}
		modal.cancel.addEventListener( 'click', closeModal );
		modal.form.addEventListener( 'submit', saveVerse );
		modal.reference.addEventListener( 'blur', fetchVerse );
		modal.reference.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' ) {
				e.preventDefault();
				fetchVerse();
			}
		} );
		modal.overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === modal.overlay ) {
				closeModal();
			}
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && ! modal.overlay.hidden ) {
				closeModal();
			}
		} );
		refreshModalLabels();
	}

	/* Init ------------------------------------------------------------- */
	render();
} )();
