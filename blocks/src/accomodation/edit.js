import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import { useSelect } from '@wordpress/data';
import {
    FormTokenField,
    Spinner,
    CheckboxControl,
    ToggleControl,
    PanelBody,
    TextControl 
} from '@wordpress/components';

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
        <>
            <InspectorControls>
                <PanelBody title={__('Location Settings', 'tsjippy')} initialOpen={true}>
                    <TextControl
                        label    = "Location Name"
                        value    = { attributes.name }
                        onChange = { (value) => setAttributes({ name: value }) }
                    />

                    <ToggleControl
                        label    = {__("Required", "tsjippy")}
                        checked  = {!!attributes.required}
                        onChange = {() => setAttributes({ required: !attributes.required }) }
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
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
            </div>
        </>
    );
}