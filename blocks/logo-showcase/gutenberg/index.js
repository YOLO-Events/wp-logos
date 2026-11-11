/**
 * WP Logos – Logo Showcase Block
 * Block registration entry point.
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import save from './save';

import './editor.scss';

registerBlockType( metadata.name, {
	...metadata,
	edit: Edit,
	save,
} );
