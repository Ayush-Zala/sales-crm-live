export default function DetailsReportTableCell({ row, column }) {
    switch (column.id) {
        case "name":
            return row.name;
        case "totalCalls":
            return row.totalCalls;
        case "zoomCalls":
            return row.zoomCalls;
        case "crmCalls":
            return row.crmCalls;
        case "totalSales":
            return row.totalSales;
        default:
            return null;
    }
}
