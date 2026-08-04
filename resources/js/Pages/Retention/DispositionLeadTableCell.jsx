import { Typography } from "@mui/material";

export default function DispositionLeadTableCell({ row, column }) {
    const { auth } = usePage().props;
    const hasPerm = hasPermission(auth, "Can View Retention Phone");

    switch (column.id) {
        case "description":
            return row.description || "Description not Provided";
        case "followup_date":
            return row.followup_date || "NA";
        case "followup_time":
            return row.followup_time || "NA";
        case "phone":
            return hasPerm ? row.phone : row.user;
        case "timezone":
            return row.timezone || "NA";
        case "status":
            return row.status;
        default:
            return null;
    }
}

// if (props.column.name === "description") {
//     return (
//         <Table.Cell>
//             <Typography variant="body2">
//                 {props.row.description || "Description not Provided"}
//             </Typography>
//         </Table.Cell>
//     );
// }

// if (props.column.name === "followup_date") {
//     return (
//         <Table.Cell>
//             <Typography variant="body2">
//                 {props.row.followup_date || "NA"}
//             </Typography>
//         </Table.Cell>
//     );
// }

// if (props.column.name === "followup_time") {
//     return (
//         <Table.Cell>
//             <Typography variant="body2">
//                 {props.row.followup_time || "NA"}
//             </Typography>
//         </Table.Cell>
//     );
// }

// return <Table.Cell {...props} />;
