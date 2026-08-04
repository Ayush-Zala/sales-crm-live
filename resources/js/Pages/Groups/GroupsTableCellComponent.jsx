import { Table } from "@devexpress/dx-react-grid-material-ui";
import EditGroup from "./dialogs/EditGroup";

export default function GroupsTableCellComponent(props) {
    if (props.column.name === "actions") {
        return (
            <Table.Cell {...props}>
                <EditGroup group={props.row} />
            </Table.Cell>
        );
    }

    return <Table.Cell {...props}>{props.value}</Table.Cell>;
}
