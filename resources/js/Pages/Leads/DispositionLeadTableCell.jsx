import { Table } from "@devexpress/dx-react-grid-material-ui";
import { Typography } from "@mui/material";

export default function DispositionLeadTableCell(props) {
    // const dispositionId = props.row.dispositionId;
    // const description = props.row.description;

    if (props.column.name === "description") {
        return (
            <Table.Cell>
                <Typography variant="body2">
                    {props.row.description || "Description not Provided"}
                </Typography>
            </Table.Cell>
        );
    }

    if (props.column.name === "followup_date") {
        return (
            <Table.Cell>
                <Typography variant="body2">
                    {props.row.followup_date || "NA"}
                </Typography>
            </Table.Cell>
        );
    }

    if (props.column.name === "followup_time") {
        return (
            <Table.Cell>
                <Typography variant="body2">
                    {props.row.followup_time || "NA"}
                </Typography>
            </Table.Cell>
        );
    }

    return <Table.Cell {...props} />;
}
