//(function () {
'use strict';

/**
 * Function, that put the data to template block, and return complete HTML.
 *
 * @param str
 * @param data
 * @returns {Function}
 */
function tmpl( str, data ) {
	// Figure out if we're getting a template, or if we need to
	// load the template - and be sure to cache the result.
	let fn = ! /\W/.test( str ) ?
		cache[ str ] = cache[ str ] ||
		               tmpl( document.getElementById( str ).innerHTML ) :

		// Generate a reusable function that will serve as a template
		// generator (and which will be cached).
		new Function( 'obj',
			'var p=[],print=function(){p.push.apply(p,arguments);};' +

			// Introduce the data as local variables using with(){}
			'with(obj){p.push(\'' +

			// Convert the template into pure JavaScript
			str
			//.toString()
				.replace( /[\r\t\n]/g, ' ' )
				.split( '<%' ).join( '\t' )
				.replace( /((^|%>)[^\t]*)'/g, '$1\r' )
				.replace( /\t=(.*?)%>/g, '\',$1,\'' )
				.split( '\t' ).join( '\');' )
				.split( '%>' ).join( 'p.push(\'' )
				.split( '\r' ).join( '\\\'' )
			+ '\');}return p.join(\'\');' );
	// Provide some basic currying to the user
	return data ? fn( data ) : fn;
}

/**
 * Get request.
 *
 * @param options
 * @returns {Promise<any>}
 */
function get_contents( options ) {
	return new Promise( function ( resolve, reject ) {
		let xhr = new XMLHttpRequest();
		let params = options.data;
		// We'll need to stringify if we've been given an object
		// If we have a string, this is skipped.
		if ( params && typeof params === 'object' ) {
			params = Object.keys( params ).map( function ( key ) {
				return encodeURIComponent( key ) + '=' + encodeURIComponent( params[ key ] );
			} ).join( '&' );
			params = '?' + params;
		} else {
			params = '';
		}

		xhr.open( options.method, options.url + params );
		xhr.onload = function () {
			if ( this.status >= 200 && this.status < 300 ) {
				resolve( xhr.response );
			} else {
				reject( {
					status: this.status,
					statusText: xhr.statusText
				} );
			}
		};
		xhr.onerror = function () {
			reject( {
				status: this.status,
				statusText: xhr.statusText
			} );
		};
		if ( options.headers ) {
			Object.keys( options.headers ).forEach( function ( key ) {
				xhr.setRequestHeader( key, options.headers[ key ] );
			} );
		}
		xhr.send( params );
	} );
}

/**
 * Event listner.
 *
 * @param e
 * @param selector
 * @param func
 */
function on( e, selector, func ) {
	document.addEventListener( e, function ( event ) {

		// if cart button clicked
		if ( event.target.closest( selector ) !== null ) {

			func( event );
		}
	} );

}

/**
 * Show search form on a click.
 */
on( 'click', '.js-game-search-button', function ( event ) {
	event.preventDefault();
	document.getElementsByClassName( 'js-game-search-form' )[ 0 ].classList.add( 'active' );
	document.getElementsByClassName( 'js-game-search-get' )[ 0 ].focus();
} );

/**
 * Add a shortcode to the content area.
 */
on( 'click', '.js-game-search-add', function ( event ) {
	event.preventDefault();
	let element = event.target.closest( '.js-game-search-add' );
	let game_id = element.getAttribute( 'data-game_id' );
	let title = element.getAttribute( 'data-title' );
	let url = element.getAttribute( 'data-url' );
	document.getElementsByClassName( 'js-game-search-get' )[ 0 ].value = '';
	document.getElementsByClassName( 'js-game-search-result' )[ 0 ].innerHTML = '';
	document.getElementsByClassName( 'js-game-search-form' )[ 0 ].classList.remove( 'active' );
	wp.media.editor.insert( '[game ' +
	                        //'id="' + game_id + '" ' +
	                        'title="' + title + '" ' +
	                        'url="' + url + '"' +
	                        ']' );
} );

/**
 * Hide search form.
 */
on( 'click', '.js-game-search-close', function ( event ) {
	event.preventDefault();
	document.getElementsByClassName( 'js-game-search-get' )[ 0 ].value = '';
	document.getElementsByClassName( 'js-game-search-result' )[ 0 ].innerHTML = '';
	document.getElementsByClassName( 'js-game-search-form' )[ 0 ].classList.remove( 'active' );
} );

/**
 * Request for games list.
 */
on( 'keyup', '.js-game-search-get', function ( event ) {

	if ( event.keyCode === 13 ) {
		event.preventDefault();
		let element = event.target;
		let search = element.value;
		let template = document.getElementById( 'js-game-search-template' ).innerHTML;
		let field = document.getElementsByClassName( 'js-game-search-field' )[ 0 ];
		field.classList.add( 'active' );

		get_contents( {
			method: 'POST',
			url: matchcenter.ajax_url,
			data: {
				action: 'oimatchcenter-get_games',
				search: search,
				search_season: search,
			},
		} )
			.then( function ( result ) {

				let data = JSON.parse( result );
				let container = document.getElementsByClassName( 'game-search__result' )[ 0 ];
				if ( data.hasOwnProperty( '_embedded' ) ) {
					let count = data._embedded.length;
					container.innerHTML = '';
					for ( let i = 0; i < count; i ++ ) {
						let scheduled = new Date( data._embedded[ i ].scheduled );

						scheduled = [
							            scheduled.getDay() < 10 ? '0' + scheduled.getDay() : scheduled.getDay(),
							            scheduled.getMonth() < 9 ? '0' + (
							                                       scheduled.getMonth() + 1
							            ) : scheduled.getMonth(),
							            scheduled.getFullYear()
						            ].join( '.' ) + ', ' + [ scheduled.getHours(), scheduled.getMinutes() ].join( ':' );
						let title_full = data._embedded[ i ].title_full;
						let teams = title_full.split( ' - ' );

						if ( 4 === teams.length ) {
							teams = [
								[ teams[ 0 ], teams[ 1 ] ].join( ' - ' ),
								[ teams[ 2 ], teams[ 3 ] ].join( ' - ' )
							];
						}
						let game = {
							'url': data._embedded[ i ].url,
							'game_id': data._embedded[ i ].game_id,
							'title_full': title_full,
							'team_1': teams[ 0 ],
							'team_2': teams[ 1 ],
							'scheduled': scheduled,
							'score': data._embedded[ i ].score.printable,
							'team_flag_1': data._embedded[ i ].participants[ 0 ].images.logo,
							'team_flag_2': data._embedded[ i ].participants[ 1 ].images.logo,
							'season_image': data._embedded[ i ].season.images.logo,
							'season_title': data._embedded[ i ].season_title,
							'is_game_ended': data._embedded[ i ].status.is_game_ended,
						};

						let html = tmpl( template, game );
						container.innerHTML += html;
					}
					field.classList.remove( 'active' );
				}
			} )
			.catch( function ( err ) {
				if ( err.hasOwnProperty( 'statusText' ) ) {
					console.error( 'There was an error!', err.statusText );
				} else {
					console.error( err );
				}
				field.classList.remove( 'active' );
			} );
	}
} );


//	}());
