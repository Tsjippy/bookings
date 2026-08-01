import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import {
    FormTokenField,
    Spinner,
    CheckboxControl,
    ToggleControl
} from '@wordpress/components';

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

	// Map subject titles to subject ids
    const subjectMap = (subjects || []).reduce((acc, subject) => {
        acc[subject.title.rendered] = subject.id;
        return acc;
    }, {});

    return (
        <fieldset {...blockProps}>
            <legend>
                {__('Accommodations Selector', 'tsjippy')}
            </legend>

            <ToggleControl
                label    = {__("Required", "tsjippy")}
                checked  = {!!attributes.required}
                onChange = {() => setAttributes({ required: !attributes.required }) }
            />

            {!subjects ? (
                <Spinner />
            ) : subjects.length < 10 ? (
                <div className="booking-subject-checkboxes">
                    {subjects.map((subject) => (
                        <CheckboxControl
                            key={subject.id}
                            label={subject.title.rendered}
                            checked={attributes.bookingSubjects.includes(
                                subject.id
                            )}
                            onChange={(checked) => {
                                const bookingSubjects = checked
                                    ? [
                                          ...attributes.bookingSubjects,
                                          subject.id,
                                      ]
                                    : attributes.bookingSubjects.filter(
                                          (id) => id !== subject.id
                                      );

                                setAttributes({
                                    bookingSubjects,
                                });
                            }}
                        />
                    ))}
                </div>
            ) : (
                <FormTokenField
                    label={__(
                        'Selectable Accommodations',
                        'tsjippy'
                    )}
                    value={attributes.bookingSubjects
                        .map(
                            (id) =>
                                subjects.find(
                                    (subject) => subject.id === id
                                )?.title.rendered
                        )
                        .filter(Boolean)}
                    placeholder={__(
                        'Start typing a accommodation name...',
                        'tsjippy'
                    )}
                    __experimentalExpandOnFocus={true}
                    suggestions={subjects.map(
                        (subject) => subject.title.rendered
                    )}
                    onChange={(tokens) => {
                        const ids = tokens
                            .map((token) => subjectMap[token])
                            .filter(Boolean);

                        setAttributes({
                            bookingSubjects: ids,
                        });
                    }}
                />
            )}
        </fieldset>
    );
}