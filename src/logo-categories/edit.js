import { __, sprintf } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	Button,
	CheckboxControl,
	ColorPicker,
	Notice,
	PanelBody,
	RangeControl,
	SelectControl,
	Spinner,
	ToggleControl,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * Category selection, ordering and visual settings with a server-rendered preview.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Saved settings.
 * @param {Function} props.setAttributes Settings updater.
 * @return {Element} Block editor.
 */
export default function Edit( { attributes, setAttributes } ) {
	const [ categories, setCategories ] = useState( null );
	const [ error, setError ] = useState( '' );
	const { categoryOrder } = attributes;
	const blockProps = useBlockProps();

	useEffect( () => {
		let cancelled = false;
		async function loadCategories() {
			try {
				let page = 1;
				let pages = 1;
				const terms = [];
				do {
					const response = await apiFetch( {
						path: `/wp/v2/wp-logo-categories?per_page=100&hide_empty=false&orderby=name&order=asc&page=${ page }`,
						parse: false,
					} );
					pages = Number( response.headers.get( 'X-WP-TotalPages' ) );
					terms.push( ...( await response.json() ) );
					page++;
				} while ( page <= pages );
				if ( ! cancelled ) {
					setCategories( terms );
				}
			} catch ( failure ) {
				if ( ! cancelled ) {
					setError(
						failure.message ||
							__(
								'Could not load logo categories.',
								'yolo-logos'
							)
					);
				}
			}
		}
		loadCategories();
		return () => {
			cancelled = true;
		};
	}, [] );

	const moveCategory = ( index, offset ) => {
		const next = [ ...categoryOrder ];
		[ next[ index ], next[ index + offset ] ] = [
			next[ index + offset ],
			next[ index ],
		];
		setAttributes( { categoryOrder: next } );
	};

	const ranges = [
		[
			'logoMaxWidth',
			__( 'Logo maximum width (px)', 'yolo-logos' ),
			40,
			600,
		],
		[
			'logoMaxHeight',
			__( 'Logo maximum height (px)', 'yolo-logos' ),
			20,
			300,
		],
		[
			'flexboxLogoWidth',
			__( 'Logo item width (px)', 'yolo-logos' ),
			60,
			600,
		],
		[ 'gap', __( 'Logo gap (px)', 'yolo-logos' ), 0, 80 ],
		[ 'padding', __( 'Logo padding (px)', 'yolo-logos' ), 0, 60 ],
		[ 'borderWidth', __( 'Logo border width (px)', 'yolo-logos' ), 0, 10 ],
		[
			'borderRadius',
			__( 'Logo border radius (px)', 'yolo-logos' ),
			0,
			100,
		],
	];
	const colors = [
		[ 'backgroundColor', __( 'Logo background', 'yolo-logos' ) ],
		[ 'borderColor', __( 'Logo border color', 'yolo-logos' ) ],
	];

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Categories and order', 'yolo-logos' ) }>
					<p>
						{ __(
							'Select categories, then move them into display order. Categories without published logos with images are hidden.',
							'yolo-logos'
						) }
					</p>
					{ error && (
						<Notice status="error" isDismissible={ false }>
							{ error }
						</Notice>
					) }
					{ ! categories && ! error && <Spinner /> }
					{ categories?.length === 0 && (
						<p>
							{ __(
								'Add categories under Logos > Categories.',
								'yolo-logos'
							) }
						</p>
					) }
					{ categories?.map( ( category ) => (
						<CheckboxControl
							key={ category.id }
							label={ category.name }
							checked={ categoryOrder.includes( category.id ) }
							onChange={ ( checked ) =>
								setAttributes( {
									categoryOrder: checked
										? [ ...categoryOrder, category.id ]
										: categoryOrder.filter(
												( id ) => id !== category.id
										  ),
								} )
							}
						/>
					) ) }
					<ol>
						{ categoryOrder.map( ( id, index ) => {
							const name =
								categories?.find(
									( category ) => category.id === id
								)?.name ||
								sprintf(
									/* translators: %d: category ID. */
									__( 'Category #%d', 'yolo-logos' ),
									id
								);
							return (
								<li key={ id }>
									<span>{ name }</span>
									<Button
										icon="arrow-up-alt2"
										label={ sprintf(
											/* translators: %s: category name. */
											__( 'Move %s up', 'yolo-logos' ),
											name
										) }
										disabled={ index === 0 }
										onClick={ () =>
											moveCategory( index, -1 )
										}
									/>
									<Button
										icon="arrow-down-alt2"
										label={ sprintf(
											/* translators: %s: category name. */
											__( 'Move %s down', 'yolo-logos' ),
											name
										) }
										disabled={
											index === categoryOrder.length - 1
										}
										onClick={ () =>
											moveCategory( index, 1 )
										}
									/>
									<Button
										icon="no-alt"
										label={ sprintf(
											/* translators: %s: category name. */
											__( 'Remove %s', 'yolo-logos' ),
											name
										) }
										onClick={ () =>
											setAttributes( {
												categoryOrder:
													categoryOrder.filter(
														( value ) =>
															value !== id
													),
											} )
										}
									/>
								</li>
							);
						} ) }
					</ol>
				</PanelBody>
				<PanelBody
					title={ __( 'Category layout', 'yolo-logos' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Category name position', 'yolo-logos' ) }
						help={ __(
							'Always above the logos on screens smaller than 782px.',
							'yolo-logos'
						) }
						value={ attributes.headingPosition }
						options={ [
							{
								label: __( 'Left', 'yolo-logos' ),
								value: 'left',
							},
							{ label: __( 'Top', 'yolo-logos' ), value: 'top' },
						] }
						onChange={ ( headingPosition ) =>
							setAttributes( { headingPosition } )
						}
					/>
					<RangeControl
						label={ __( 'Heading level', 'yolo-logos' ) }
						value={ attributes.headingLevel }
						min={ 2 }
						max={ 6 }
						onChange={ ( headingLevel ) =>
							setAttributes( { headingLevel } )
						}
					/>
					{ attributes.headingPosition === 'left' && (
						<RangeControl
							label={ __(
								'Heading column width (px)',
								'yolo-logos'
							) }
							value={ attributes.headingWidth }
							min={ 100 }
							max={ 400 }
							onChange={ ( headingWidth ) =>
								setAttributes( { headingWidth } )
							}
						/>
					) }
					<RangeControl
						label={ __( 'Category spacing (px)', 'yolo-logos' ) }
						value={ attributes.categoryGap }
						min={ 0 }
						max={ 100 }
						onChange={ ( categoryGap ) =>
							setAttributes( { categoryGap } )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Dividers', 'yolo-logos' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Divider style', 'yolo-logos' ) }
						value={ attributes.dividerStyle }
						options={ [
							{
								label: __( 'None', 'yolo-logos' ),
								value: 'none',
							},
							{
								label: __( 'Solid', 'yolo-logos' ),
								value: 'solid',
							},
							{
								label: __( 'Dashed', 'yolo-logos' ),
								value: 'dashed',
							},
							{
								label: __( 'Dotted', 'yolo-logos' ),
								value: 'dotted',
							},
						] }
						onChange={ ( dividerStyle ) =>
							setAttributes( { dividerStyle } )
						}
					/>
					{ attributes.dividerStyle !== 'none' && (
						<>
							<RangeControl
								label={ __(
									'Divider width (px)',
									'yolo-logos'
								) }
								value={ attributes.dividerWidth }
								min={ 1 }
								max={ 10 }
								onChange={ ( dividerWidth ) =>
									setAttributes( { dividerWidth } )
								}
							/>
							<p>{ __( 'Divider color', 'yolo-logos' ) }</p>
							<ColorPicker
								color={ attributes.dividerColor }
								onChange={ ( dividerColor ) =>
									setAttributes( { dividerColor } )
								}
							/>
						</>
					) }
				</PanelBody>
				<PanelBody
					title={ __( 'Logo appearance', 'yolo-logos' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Image variant', 'yolo-logos' ) }
						value={ attributes.theme }
						options={ [
							{
								label: __( 'Standard', 'yolo-logos' ),
								value: 'standard',
							},
							{
								label: __( 'Light', 'yolo-logos' ),
								value: 'light',
							},
							{
								label: __( 'Dark', 'yolo-logos' ),
								value: 'dark',
							},
						] }
						onChange={ ( theme ) => setAttributes( { theme } ) }
					/>
					<SelectControl
						label={ __( 'Image sizing', 'yolo-logos' ) }
						value={ attributes.logoRatio }
						options={ [
							{
								label: __(
									'Original proportions',
									'yolo-logos'
								),
								value: 'original',
							},
							{
								label: __( 'Fixed image box', 'yolo-logos' ),
								value: 'fixed',
							},
						] }
						onChange={ ( logoRatio ) =>
							setAttributes( { logoRatio } )
						}
					/>
					<SelectControl
						label={ __( 'Logo alignment', 'yolo-logos' ) }
						value={ attributes.flexboxAlign }
						options={ [
							{
								label: __( 'Left', 'yolo-logos' ),
								value: 'left',
							},
							{
								label: __( 'Center', 'yolo-logos' ),
								value: 'center',
							},
							{
								label: __( 'Right', 'yolo-logos' ),
								value: 'right',
							},
						] }
						onChange={ ( flexboxAlign ) =>
							setAttributes( { flexboxAlign } )
						}
					/>
					{ ranges.map( ( [ key, label, min, max ] ) => (
						<RangeControl
							key={ key }
							label={ label }
							min={ min }
							max={ max }
							value={ attributes[ key ] }
							onChange={ ( value ) =>
								setAttributes( { [ key ]: value } )
							}
						/>
					) ) }
					<ToggleControl
						label={ __(
							'Grayscale (color on hover or focus)',
							'yolo-logos'
						) }
						checked={ attributes.grayscale }
						onChange={ ( grayscale ) =>
							setAttributes( { grayscale } )
						}
					/>
					<ToggleControl
						label={ __( 'Show logo titles', 'yolo-logos' ) }
						checked={ attributes.showTitle }
						onChange={ ( showTitle ) =>
							setAttributes( { showTitle } )
						}
					/>
					<ToggleControl
						label={ __( 'Show logo labels', 'yolo-logos' ) }
						checked={ attributes.showLabel }
						onChange={ ( showLabel ) =>
							setAttributes( { showLabel } )
						}
					/>
					{ colors.map( ( [ key, label ] ) => (
						<div key={ key }>
							<p>{ label }</p>
							<ColorPicker
								color={ attributes[ key ] }
								onChange={ ( value ) =>
									setAttributes( { [ key ]: value } )
								}
							/>
							{ key === 'backgroundColor' && (
								<Button
									variant="secondary"
									onClick={ () =>
										setAttributes( { backgroundColor: '' } )
									}
								>
									{ __( 'Clear background', 'yolo-logos' ) }
								</Button>
							) }
						</div>
					) ) }
				</PanelBody>
			</InspectorControls>
			{ categoryOrder.length ? (
				<ServerSideRender
					block="yolo-logos/logo-categories"
					attributes={ attributes }
					httpMethod="POST"
				/>
			) : (
				<Notice status="info" isDismissible={ false }>
					{ __(
						'Choose logo categories in the block settings, then use the arrows to sort them.',
						'yolo-logos'
					) }
				</Notice>
			) }
		</div>
	);
}
