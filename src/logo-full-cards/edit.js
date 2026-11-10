import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	ColorPicker,
	PanelBody,
	RangeControl,
	SelectControl,
	ServerSideRender,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

export default function Edit( { attributes, setAttributes } ) {
	const categories = useSelect(
		( select ) =>
			select( coreStore ).getEntityRecords(
				'taxonomy',
				'wp_logo_category',
				{
					per_page: -1,
					orderby: 'name',
					order: 'asc',
				}
			) || [],
		[]
	);
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Source', 'yolo-logos' ) }>
					<SelectControl
						label={ __( 'Logo category', 'yolo-logos' ) }
						value={ attributes.categoryId }
						options={ [
							{
								label: __( 'All logos', 'yolo-logos' ),
								value: 0,
							},
							...categories.map( ( category ) => ( {
								label: category.name,
								value: category.id,
							} ) ),
						] }
						onChange={ ( categoryId ) =>
							setAttributes( {
								categoryId: Number( categoryId ),
							} )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Card layout', 'yolo-logos' ) }
					initialOpen={ false }
				>
					{ [
						[ 'mobile', __( 'Mobile columns', 'yolo-logos' ) ],
						[ 'tablet', __( 'Tablet columns', 'yolo-logos' ) ],
						[ 'laptop', __( 'Laptop columns', 'yolo-logos' ) ],
						[ 'desktop', __( 'Desktop columns', 'yolo-logos' ) ],
					].map( ( [ breakpoint, label ] ) => (
						<RangeControl
							key={ breakpoint }
							label={ label }
							value={ attributes.columns[ breakpoint ] }
							min={ 1 }
							max={ 6 }
							onChange={ ( value ) =>
								setAttributes( {
									columns: {
										...attributes.columns,
										[ breakpoint ]: value,
									},
								} )
							}
						/>
					) ) }
					<RangeControl
						label={ __( 'Card gap (px)', 'yolo-logos' ) }
						value={ attributes.gap }
						min={ 0 }
						max={ 80 }
						onChange={ ( gap ) => setAttributes( { gap } ) }
					/>
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
					{ [
						[
							'logoMaxWidth',
							__( 'Maximum logo width (px)', 'yolo-logos' ),
							40,
							600,
						],
						[
							'logoMaxHeight',
							__( 'Maximum logo height (px)', 'yolo-logos' ),
							20,
							300,
						],
						[
							'padding',
							__( 'Card padding (px)', 'yolo-logos' ),
							0,
							60,
						],
						[
							'borderWidth',
							__( 'Border width (px)', 'yolo-logos' ),
							0,
							10,
						],
						[
							'borderRadius',
							__( 'Corner radius (px)', 'yolo-logos' ),
							0,
							50,
						],
					].map( ( [ key, label, min, max ] ) => (
						<RangeControl
							key={ key }
							label={ label }
							value={ attributes[ key ] }
							min={ min }
							max={ max }
							onChange={ ( value ) =>
								setAttributes( { [ key ]: value } )
							}
						/>
					) ) }
					<p>{ __( 'Card background', 'yolo-logos' ) }</p>
					<ColorPicker
						color={ attributes.backgroundColor }
						onChange={ ( backgroundColor ) =>
							setAttributes( { backgroundColor } )
						}
					/>
					<p>{ __( 'Card border color', 'yolo-logos' ) }</p>
					<ColorPicker
						color={ attributes.borderColor }
						onChange={ ( borderColor ) =>
							setAttributes( { borderColor } )
						}
					/>
					<ToggleControl
						label={ __( 'Show logo names', 'yolo-logos' ) }
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
					<ToggleControl
						label={ __( 'Grayscale logos', 'yolo-logos' ) }
						checked={ attributes.grayscale }
						onChange={ ( grayscale ) =>
							setAttributes( { grayscale } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block="yolo-logos/logo-full-cards"
				attributes={ attributes }
				httpMethod="POST"
			/>
		</div>
	);
}
