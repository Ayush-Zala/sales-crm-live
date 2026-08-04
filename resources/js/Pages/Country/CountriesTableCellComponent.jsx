import { Table } from "@devexpress/dx-react-grid-material-ui";
import CountriesActions from "./CellComponents/CountriesActions";

export default function CountriesTableCellComponent(props) {
    if (props.column.name === "actions") {
        return (
            <Table.Cell {...props}>
                <CountriesActions country={props.row} />
            </Table.Cell>
        );
    }

    return <Table.Cell {...props}>{props.value}</Table.Cell>;
}
