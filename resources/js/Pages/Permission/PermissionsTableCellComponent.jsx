import EditPermission from "./dialogs/EditPermission";

export default function PermissionsTableCellComponent({ row, column }) {
    switch (column.id) {
        case "actions":
            return <EditPermission permission={row} />;
        default:
            return row[column.id];
    }
}
