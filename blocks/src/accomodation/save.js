/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InnerBlocks  } from '@wordpress/block-editor';
import { Multiple } from './../input/components/Multiple.js';

/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#save
 *
 * @return {Element} Element to render.
 */
export default function save({ attributes }) {
	const blockProps 		= useBlockProps.save();

	const labelComponent	= (addBlockId) => {
		return (
			<label 
				data-blockid={addBlockId ? attributes.blockId : undefined}
				className= {attributes.childAttr.hidden ? 'hidden' : undefined}
			>
				<h4 class="label-text">
					{attributes.text}
				</h4>
				
				<br></br>
				<InnerBlocks.Content />
			</label>
		);
	};

	return (
		attributes.childAttr.multiple ?
			<Multiple
				inner      = { labelComponent(false) }
				attributes = { attributes.childAttr }
			/>
		:
			labelComponent(true)
	);
}
