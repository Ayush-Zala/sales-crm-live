import RoleActions from "./CellComponents/RoleActions";
import RoleLinkComponent from "./CellComponents/RoleLinkComponent";
import { formatDateTime } from "@/utils/date-time-formatters";

export default function RolesTableCellComponent({ row, column }) {
    switch (column.id) {
        case "id":
            return row[column.id];
        case "name":
            return <RoleLinkComponent role={row} />;
        case "guard_name":
            return row[column.id];
        case "created_at":
        case "updated_at":
            return formatDateTime(row[column.id]);
        case "actions":
            return <RoleActions role={row} />;
        default:
            return null;
    }
}

// if (props.column.name === "name") {
//     return (
//         <Table.Cell {...props}>
//             <RoleLinkComponent role={props.row} />
//         </Table.Cell>
//     );
// }

// if (props.column.name === "created_at" || props.column.name === "updated_at") {
//     return <Table.Cell {...props}>{formatDateTime(props.value)}</Table.Cell>;
// }

// if (props.column.name === "actions") {
//     return (
//         <Table.Cell {...props}>
//             <RoleActions role={props.row} />
//         </Table.Cell>
//     );
// }

// return <Table.Cell {...props}>{props.value}</Table.Cell>;
