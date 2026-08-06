/**
 * WP Logos – Logo Showcase Block – Edit
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	SelectControl,
	RangeControl,
	ToggleControl,
	TextareaControl,
	TabPanel,
	ColorPicker,
	__experimentalBoxControl as BoxControl,
	Spinner,
	Notice,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { useMemo } from '@wordpress/element';

/**
 * Renders a single logo preview thumbnail in the editor.
 *
 * @param {Object} props
 * @param {Object} props.logo      Logo REST object.
 * @param {Object} props.attributes Block attributes.
 */
function LogoPreviewItem( { logo, attributes } ) {
	const { logoMaxHeight, logoMaxWidth, padding, grayscale, showTitle } = attributes;

	// Use the featured image for editor preview (embedded via REST).
	const featuredMedia = logo._embedded?.[ 'wp:featuredmedia' ]?.[ 0 ];
	const thumbUrl = featuredMedia?.source_url || null;

	const imgStyle = {
		maxHeight: `${ logoMaxHeight || 80 }px`,
		maxWidth:  `${ logoMaxWidth || 160 }px`,
		filter:    grayscale ? 'grayscale(100%)' : 'none',
		opacity:   grayscale ? 0.7 : 1,
		display:   'block',
	};

	const itemStyle = {
		display:        'flex',
		alignItems:     'center',
		justifyContent: 'center',
		flexDirection:  'column',
		padding:        `${ padding || 15 }px`,
		border:         attributes.borderWidth ? `${ attributes.borderWidth }px solid ${ attributes.borderColor || '#e0e0e0' }` : 'none',
		borderRadius:   attributes.borderRadius ? `${ attributes.borderRadius }px` : 0,
		background:     attributes.backgroundColor || 'transparent',
		boxSizing:      'border-box',
	};

	return (
		<div className="wp-logos-editor-item" style={ itemStyle }>
			{ thumbUrl ? (
				<img
					src={ thumbUrl }
					alt={ logo.title?.rendered || '' }
					style={ imgStyle }
				/>
			) : (
				<span
					className="dashicons dashicons-images-alt"
					style={ { fontSize: 40, color: '#ccc', display: 'block' } }
				/>
			) }
			{ showTitle && (
				<span style={ { fontSize: '0.8em', marginTop: 6, opacity: 0.7 } }>
					{ logo.title?.rendered }
				</span>
			) }
		</div>
	);
}

/**
 * Column settings sub-panel (used for Grid & Carousel).
 */
function ColumnSettings( { columns, onChange } ) {
	const breakpoints = [
		{ key: 'mobile',  label: __( 'Mobile (< 600px)', 'wp-logos' ) },
		{ key: 'tablet',  label: __( 'Tablet (600–900px)', 'wp-logos' ) },
		{ key: 'laptop',  label: __( 'Laptop (900–1200px)', 'wp-logos' ) },
		{ key: 'desktop', label: __( 'Desktop (≥ 1200px)', 'wp-logos' ) },
	];

	return (
		<>
			{ breakpoints.map( ( bp ) => (
				<RangeControl
					key={ bp.key }
					label={ bp.label }
					value={ columns[ bp.key ] || 2 }
					onChange={ ( val ) => onChange( { ...columns, [ bp.key ]: val } ) }
					min={ 1 }
					max={ 12 }
				/>
			) ) }
		</>
	);
}

/**
 * Main Edit component.
 */
export default function Edit( { attributes, setAttributes } ) {
	const {
		categoryId,
		showcaseType,
		theme,
		logoRatio,
		columns,
		gap,
		showTitle,
		grayscale,
		carouselAutoplay,
		carouselSpeed,
		carouselInfinite,
		carouselArrows,
		carouselDots,
		carouselTicker,
		carouselTickerSpeed,
		flexboxAlign,
		flexboxLogoWidth,
		logoMaxHeight,
		logoMaxWidth,
		padding,
		backgroundColor,
		borderWidth,
		borderColor,
		borderRadius,
		customCSS,
	} = attributes;

	/* --- Fetch categories --- */
	const { categories, categoriesLoaded } = useSelect( ( select ) => {
		const { getEntityRecords, hasFinishedResolution } = select( coreStore );
		const query = { per_page: -1, orderby: 'name', order: 'asc' };
		const terms = getEntityRecords( 'taxonomy', 'wp_logo_category', query );
		const done  = hasFinishedResolution( 'getEntityRecords', [
			'taxonomy',
			'wp_logo_category',
			query,
		] );
		return { categories: terms || [], categoriesLoaded: done };
	}, [] );

	/* --- Fetch logos for preview --- */
	const logoQuery = useMemo( () => {
		const q = { per_page: 20, _embed: true };
		if ( categoryId ) {
			q.wp_logo_category = categoryId;
		}
		return q;
	}, [ categoryId ] );

	const { logos, logosLoaded } = useSelect(
		( select ) => {
			const { getEntityRecords, hasFinishedResolution } = select( coreStore );
			const posts = getEntityRecords( 'postType', 'wp_logo', logoQuery );
			const done  = hasFinishedResolution( 'getEntityRecords', [
				'postType',
				'wp_logo',
				logoQuery,
			] );
			return { logos: posts || [], logosLoaded: done };
		},
		[ logoQuery ]
	);

	/* --- Category options for the SelectControl --- */
	const categoryOptions = useMemo( () => {
		const opts = [ { label: __( '— All Logos —', 'wp-logos' ), value: 0 } ];
		categories.forEach( ( cat ) => {
			opts.push( { label: cat.name, value: cat.id } );
		} );
		return opts;
	}, [ categories ] );

	/* --- Block props --- */
	const blockProps = useBlockProps( {
		className: `wp-logos-showcase wp-logos-${ showcaseType } wp-logos-theme-${ theme }`,
	} );

	/* --- Preview grid style --- */
	const previewGridStyle = useMemo( () => {
		if ( showcaseType === 'flexbox' ) {
			const justifyMap = { left: 'flex-start', center: 'center', right: 'flex-end' };
			return {
				display:        'flex',
				flexWrap:       'wrap',
				justifyContent: justifyMap[ flexboxAlign ] || 'center',
				gap:            `${ gap }px`,
			};
		}
		// Grid (default for editor preview – carousel looks same).
		const cols = columns.desktop || 5;
		return {
			display:             'grid',
			gridTemplateColumns: `repeat(${ Math.min( cols, logos.length || cols ) }, 1fr)`,
			gap:                 `${ gap }px`,
		};
	}, [ showcaseType, columns, gap, flexboxAlign, logos.length ] );

	/* -------------------------------------------------------------------
	 * Inspector Controls
	 * ----------------------------------------------------------------- */
	const inspector = (
		<InspectorControls>
			{/* ---- Source ---- */}
			<PanelBody title={ __( 'Source', 'wp-logos' ) } initialOpen={ true }>
				{ ! categoriesLoaded ? (
					<Spinner />
				) : (
					<SelectControl
						label={ __( 'Logo Category', 'wp-logos' ) }
						value={ categoryId }
						options={ categoryOptions }
						onChange={ ( val ) => setAttributes( { categoryId: parseInt( val, 10 ) } ) }
					/>
				) }
			</PanelBody>

			{/* ---- Showcase Type ---- */}
			<PanelBody title={ __( 'Showcase Type', 'wp-logos' ) } initialOpen={ true }>
				<SelectControl
					label={ __( 'Type', 'wp-logos' ) }
					value={ showcaseType }
					options={ [
						{ label: __( 'Grid', 'wp-logos' ),     value: 'grid' },
						{ label: __( 'Carousel', 'wp-logos' ), value: 'carousel' },
						{ label: __( 'Flexbox', 'wp-logos' ),  value: 'flexbox' },
					] }
					onChange={ ( val ) => setAttributes( { showcaseType: val } ) }
				/>
				<SelectControl
					label={ __( 'Logo Theme', 'wp-logos' ) }
					value={ theme }
					options={ [
						{ label: __( 'Standard', 'wp-logos' ), value: 'standard' },
						{ label: __( 'Light',    'wp-logos' ), value: 'light' },
						{ label: __( 'Dark',     'wp-logos' ), value: 'dark' },
					] }
					onChange={ ( val ) => setAttributes( { theme: val } ) }
					help={ __( 'Selects the logo image variant to display.', 'wp-logos' ) }
				/>
				<SelectControl
					label={ __( 'Logo Ratio', 'wp-logos' ) }
					value={ logoRatio }
					options={ [
						{ label: __( 'Original (max-width / max-height)', 'wp-logos' ), value: 'original' },
						{ label: __( 'Fixed (exact width × height)',       'wp-logos' ), value: 'fixed' },
					] }
					onChange={ ( val ) => setAttributes( { logoRatio: val } ) }
				/>
			</PanelBody>

			{/* ---- Grid / Columns ---- */}
			{ ( showcaseType === 'grid' || showcaseType === 'carousel' ) && (
				<PanelBody title={ __( 'Columns', 'wp-logos' ) } initialOpen={ false }>
					<ColumnSettings
						columns={ columns }
						onChange={ ( val ) => setAttributes( { columns: val } ) }
					/>
				</PanelBody>
			) }

			{/* ---- Flexbox ---- */}
			{ showcaseType === 'flexbox' && (
				<PanelBody title={ __( 'Flexbox Options', 'wp-logos' ) } initialOpen={ false }>
					<SelectControl
						label={ __( 'Alignment', 'wp-logos' ) }
						value={ flexboxAlign }
						options={ [
							{ label: __( 'Left',   'wp-logos' ), value: 'left' },
							{ label: __( 'Center', 'wp-logos' ), value: 'center' },
							{ label: __( 'Right',  'wp-logos' ), value: 'right' },
						] }
						onChange={ ( val ) => setAttributes( { flexboxAlign: val } ) }
					/>
					<RangeControl
						label={ __( 'Logo Width (px)', 'wp-logos' ) }
						value={ flexboxLogoWidth }
						onChange={ ( val ) => setAttributes( { flexboxLogoWidth: val } ) }
						min={ 60 }
						max={ 400 }
					/>
				</PanelBody>
			) }

			{/* ---- Carousel ---- */}
			{ showcaseType === 'carousel' && (
				<PanelBody title={ __( 'Carousel Options', 'wp-logos' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Ticker Mode', 'wp-logos' ) }
						checked={ carouselTicker }
						onChange={ ( val ) => setAttributes( { carouselTicker: val } ) }
						help={ __( 'Continuous non-stop scrolling — great for sponsor strips.', 'wp-logos' ) }
					/>
					{ carouselTicker ? (
						<RangeControl
							label={ __( 'Ticker Speed (1 = normal, 2 = double speed)', 'wp-logos' ) }
							value={ carouselTickerSpeed }
							onChange={ ( val ) => setAttributes( { carouselTickerSpeed: val } ) }
							min={ 0.1 }
							max={ 5 }
							step={ 0.1 }
						/>
					) : (
						<>
							<ToggleControl
								label={ __( 'Autoplay', 'wp-logos' ) }
								checked={ carouselAutoplay }
								onChange={ ( val ) => setAttributes( { carouselAutoplay: val } ) }
							/>
							{ carouselAutoplay && (
								<RangeControl
									label={ __( 'Autoplay Speed (ms)', 'wp-logos' ) }
									value={ carouselSpeed }
									onChange={ ( val ) => setAttributes( { carouselSpeed: val } ) }
									min={ 500 }
									max={ 10000 }
									step={ 100 }
								/>
							) }
							<ToggleControl
								label={ __( 'Infinite Loop', 'wp-logos' ) }
								checked={ carouselInfinite }
								onChange={ ( val ) => setAttributes( { carouselInfinite: val } ) }
							/>
							<ToggleControl
								label={ __( 'Show Arrows', 'wp-logos' ) }
								checked={ carouselArrows }
								onChange={ ( val ) => setAttributes( { carouselArrows: val } ) }
							/>
							<ToggleControl
								label={ __( 'Show Dots', 'wp-logos' ) }
								checked={ carouselDots }
								onChange={ ( val ) => setAttributes( { carouselDots: val } ) }
							/>
						</>
					) }
				</PanelBody>
			) }

			{/* ---- Logo Dimensions ---- */}
			<PanelBody title={ __( 'Logo Dimensions', 'wp-logos' ) } initialOpen={ false }>
				<RangeControl
					label={ __( 'Max Height (px)', 'wp-logos' ) }
					value={ logoMaxHeight }
					onChange={ ( val ) => setAttributes( { logoMaxHeight: val } ) }
					min={ 20 }
					max={ 300 }
				/>
				<RangeControl
					label={ __( 'Max Width (px)', 'wp-logos' ) }
					value={ logoMaxWidth }
					onChange={ ( val ) => setAttributes( { logoMaxWidth: val } ) }
					min={ 40 }
					max={ 600 }
				/>
			</PanelBody>

			{/* ---- Visual Style ---- */}
			<PanelBody title={ __( 'Visual Style', 'wp-logos' ) } initialOpen={ false }>
				<RangeControl
					label={ __( 'Gap (px)', 'wp-logos' ) }
					value={ gap }
					onChange={ ( val ) => setAttributes( { gap: val } ) }
					min={ 0 }
					max={ 80 }
				/>
				<RangeControl
					label={ __( 'Item Padding (px)', 'wp-logos' ) }
					value={ padding }
					onChange={ ( val ) => setAttributes( { padding: val } ) }
					min={ 0 }
					max={ 60 }
				/>
				<ToggleControl
					label={ __( 'Show Logo Titles', 'wp-logos' ) }
					checked={ showTitle }
					onChange={ ( val ) => setAttributes( { showTitle: val } ) }
				/>
				<ToggleControl
					label={ __( 'Grayscale Logos', 'wp-logos' ) }
					checked={ grayscale }
					onChange={ ( val ) => setAttributes( { grayscale: val } ) }
					help={ __( 'Logos turn to color on hover.', 'wp-logos' ) }
				/>
				<hr />
				<p><strong>{ __( 'Item Background Color', 'wp-logos' ) }</strong></p>
				<ColorPicker
					color={ backgroundColor || '#ffffff' }
					onChange={ ( val ) => setAttributes( { backgroundColor: val } ) }
					enableAlpha
				/>
				<RangeControl
					label={ __( 'Border Width (px)', 'wp-logos' ) }
					value={ borderWidth }
					onChange={ ( val ) => setAttributes( { borderWidth: val } ) }
					min={ 0 }
					max={ 10 }
				/>
				{ borderWidth > 0 && (
					<>
						<p><strong>{ __( 'Border Colour', 'wp-logos' ) }</strong></p>
						<ColorPicker
							color={ borderColor || '#e0e0e0' }
							onChange={ ( val ) => setAttributes( { borderColor: val } ) }
						/>
					</>
				) }
				<RangeControl
					label={ __( 'Border Radius (px)', 'wp-logos' ) }
					value={ borderRadius }
					onChange={ ( val ) => setAttributes( { borderRadius: val } ) }
					min={ 0 }
					max={ 50 }
				/>
			</PanelBody>

			{/* ---- Custom CSS ---- */}
			<PanelBody title={ __( 'Custom CSS', 'wp-logos' ) } initialOpen={ false }>
				<TextareaControl
					label={ __( 'Custom CSS (applied to this block only)', 'wp-logos' ) }
					value={ customCSS }
					onChange={ ( val ) => setAttributes( { customCSS: val } ) }
					rows={ 6 }
					help={ __( 'CSS rules scoped to this specific showcase block via its unique ID.', 'wp-logos' ) }
				/>
			</PanelBody>
		</InspectorControls>
	);

	/* -------------------------------------------------------------------
	 * Editor Preview
	 * ----------------------------------------------------------------- */
	let previewContent;

	if ( ! logosLoaded ) {
		previewContent = (
			<div className="wp-logos-editor-loading">
				<Spinner />
				<span>{ __( 'Loading logos…', 'wp-logos' ) }</span>
			</div>
		);
	} else if ( logos.length === 0 ) {
		previewContent = (
			<Notice status="info" isDismissible={ false }>
				{ categoryId
					? __( 'No logos found in this category. Add some logos under Logos → All Logos.', 'wp-logos' )
					: __( 'No logos found. Add some logos under Logos → Add New.', 'wp-logos' ) }
			</Notice>
		);
	} else {
		previewContent = (
			<div className="wp-logos-editor-preview" style={ previewGridStyle }>
				{ logos.map( ( logo ) => (
					<LogoPreviewItem
						key={ logo.id }
						logo={ logo }
						attributes={ attributes }
					/>
				) ) }
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			{ inspector }

			<div className="wp-logos-editor-header">
				<span className="wp-logos-editor-label">
					{ __( 'WP Logos', 'wp-logos' ) }
					{ ' · ' }
					{ showcaseType.charAt( 0 ).toUpperCase() + showcaseType.slice( 1 ) }
					{ categoryId && categories.find( ( c ) => c.id === categoryId )
						? ` · ${ categories.find( ( c ) => c.id === categoryId ).name }`
						: '' }
				</span>
			</div>

			{ previewContent }
		</div>
	);
}
