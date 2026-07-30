import { __ } from "@wordpress/i18n";
import "./editor.scss";
import { useBlockProps } from "@wordpress/block-editor";

const Edit = () => {
	const blockProps = useBlockProps();

	return (
		<div {...blockProps}>
			<h2>{__("Room Details")}</h2>
			<p>
				Nothing special needed for a room. All settings are given in the
				parent accommodation post.
			</p>
		</div>
	);
};

export default Edit;