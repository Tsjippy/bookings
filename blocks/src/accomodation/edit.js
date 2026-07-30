import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import './editor.scss';

export default function Edit({ attributes }) {
	const blockProps = useBlockProps();

	return (
		<fieldset {...blockProps}>
			<legend>{__('Accommodation Selector', 'tsjippy')}</legend>

			<ServerSideRender
				block="tsjippy-bookings/accomodation"
				attributes={attributes}
			/>
		</fieldset>
	);
}