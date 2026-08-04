import { Table } from "@devexpress/dx-react-grid-material-ui";
import StateActions from "./CellComponents/StateActions";

export default function StatesTableCellComponent(props) {
    if (props.column.name === "actions") {
        return (
            <Table.Cell {...props}>
                <StateActions state={props.row} />
            </Table.Cell>
        );
    }

    return <Table.Cell {...props}>{props.value}</Table.Cell>;
}
