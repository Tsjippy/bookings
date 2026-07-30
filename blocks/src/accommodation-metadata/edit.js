import {
    PanelBody,
    ToggleControl,
    SelectControl,
    FormTokenField,
    TextControl 
} from "@wordpress/components";

import { useSelect } from '@wordpress/data';
import { useEntityProp, store as coreDataStore } from "@wordpress/core-data";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";

const Edit = () => {
  const blockProps = useBlockProps();
  const postType = useSelect(
    (select) => select("core/editor").getCurrentPostType(),
    [],
  );

  const [meta, setMeta] = useEntityProp("postType", postType, "meta"); 

  console.log(meta);

  const allUsers = useSelect(
    (select) =>
        select(coreDataStore).getUsers({
            per_page: 100,
        }) || [],
    []
  );

  const selectedUsers = (allUsers || []).filter((user) =>
      (meta?.tsjippy_managers || []).includes(user.id)
  );

  return (
  <>
    <InspectorControls>
      <PanelBody title="Accommodation Settings" initialOpen={true}>
        <FormTokenField
          label="Managers of this accommodation"
          value={selectedUsers.map((user) => user.name)}
          suggestions={(allUsers || []).map((user) => user.name)}
          onChange={(tokens) => {
              const managers = (allUsers || [])
                .filter((user) => tokens.includes(user.name))
                .map((user) => user.id);

              setMeta({
                ...(meta || {}),
                tsjippy_managers: managers,
              })
            }}
        />

        <ToggleControl
            label="Enable Payments"
            checked={ !!meta?.tsjippy_payments }
            onChange={ (payments) => setMeta({
                ...(meta || {}),
                tsjippy_payments: payments,
              }) }
        />

        <ToggleControl
            label="Allow new arrivals on the day the previous people leave"
            checked={ !!meta?.tsjippy_overlap }
            onChange={ (overlap) => setMeta({
                ...(meta || {}),
                tsjippy_overlap: overlap,
              }) }
        />

        <TextControl
            label="Minimum time between two bookings in days"
            type="number"
            min={ 0 }
            help="Use 0 for allowing guests to arrive the next day. 1 means there is one full day between the previous and the next booking."
            value={ meta?.tsjippy_overlap_period ?? 0}
            onChange={ (overlapPeriod) =>
                setMeta({
                  ...(meta || {}),
                  tsjippy_overlap_period: parseInt(overlapPeriod, 10) || 0,
                })
            }
        />

        <ToggleControl
            label="Allow one day events"
            checked={ !!meta?.tsjippy_oneday }
            onChange={ (oneday) => setMeta({
                ...(meta || {}),
                tsjippy_oneday: oneday,
              }) }
        />

        <SelectControl
            label="Default status for new bookings"
            value={ meta?.tsjippy_default_booking_state ?? "confirmed"}
            options={[
                { label: 'Pending', value: 'pending' },
                { label: 'Confirmed', value: 'confirmed' },
            ]}
            onChange={ (default_booking_state) =>
              setMeta({
                  ...(meta || {}),
                  tsjippy_default_booking_state:default_booking_state,
              })
            }
        />
      </PanelBody>
    </InspectorControls>

    <div {...blockProps}>
      <h3>Accommodation Settings</h3>
      <table>
        <thead>
          <tr>
            <th>Setting</th>
            <th>Value</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              Managers
              </td>
            <td>
              {selectedUsers.map((user) => user.name).join(', ')}
            </td>
          </tr>
          <tr>
            <td>
              Automatic Payment Reminders
            </td>
            <td>
              { !!meta?.tsjippy_payments ? "Enabled" : "Disabled" }
            </td>
          </tr>
          <tr>
            <td>
              Allow new arrivals on the day the previous people leave
            </td>
            <td>
              { !!meta?.tsjippy_overlap ? "Enabled" : "Disabled"  }
            </td>
          </tr>
          <tr>
            <td>
              Minimum time between two bookings in days
            </td>
            <td>
              { meta?.tsjippy_overlap_period ?? 0}
            </td>
          </tr>
          <tr>
            <td>
              Allow one day events
            </td>
            <td>
              { !!meta?.tsjippy_oneday ? "Enabled" : "Disabled"  }
            </td>
          </tr>
          <tr>
            <td>
              Default status for new bookings
            </td>
            <td>
              { (meta?.tsjippy_default_booking_state ?? "confirmed") == '' ? "confirmed" : meta?.tsjippy_default_booking_state ?? "confirmed"}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    </>
  );
};

  

export default Edit;
