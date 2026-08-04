import { Table } from "@devexpress/dx-react-grid-material-ui";

export default function CitiesTableCellComponent(props) {
    return <Table.Cell {...props}>{props.value}</Table.Cell>;
}
