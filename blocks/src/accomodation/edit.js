import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import ServerSideRender from '@wordpress/server-side-render';

import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
	const blockProps = useBlockProps();

	const subjects = useSelect(
		(select) =>
			select('core').getEntityRecords('postType', 'booking-subject', {
				per_page: -1,
			}),
		[]
	);

	return (
		<fieldset {...blockProps}>
			<legend>
				{__('Accommodations Selector', 'tsjippy')}
			</legend>

			<select
				multiple
				value={attributes.bookingSubjects.map(String)}
				onChange={(event) => {
					const values = Array.from(
						event.target.selectedOptions,
						(option) => parseInt(option.value, 10)
					);

					setAttributes({
						bookingSubjects: values,
					});
				}}
				style={{
					width: '100%',
					minHeight: '150px',
				}}
			>
				{(subjects || []).map((subject) => (
					<option
						key={subject.id}
						value={subject.id}
					>
						{subject.title.rendered}
					</option>
				))}
			</select>

			<ServerSideRender
				block="tsjippy-bookings/accomodation"
				attributes={attributes}
			/>
		</fieldset>
	);
}