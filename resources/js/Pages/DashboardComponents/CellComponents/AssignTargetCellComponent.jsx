import { Table } from "@devexpress/dx-react-grid-material-ui";
import TargetAssignToUser from "./TargetAssignToUser";

export default function AssignTargetCellComponent(props) {
    if (props.column.name === "target_achieved") {
        return (
            <Table.Cell {...props}>
                <TargetAssignToUser rowData={props.row} />
            </Table.Cell>
        );
    }

    return <Table.Cell {...props}>{props.value}</Table.Cell>;
}
