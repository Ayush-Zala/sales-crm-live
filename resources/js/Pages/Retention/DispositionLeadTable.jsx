import SimpleDataTable from "@/Components/SimpleDataTable";
import DispositionLeadTableCell from "./DispositionLeadTableCell";

const DispositionLeadTable = ({ dispositions }) => {
    const columns = [
        { id: "dispositionId", label: "ID" },
        { id: "description", label: "Description" },
        { id: "followup_date", label: "Followup Date" },
        { id: "followup_time", label: "Followup Time" },
        { id: "phone", label: "Phone" },
        { id: "timezone", label: "Time zone" },
        { id: "status", label: "Status" },
    ];

    return (
        <SimpleDataTable
            columns={columns}
            rows={dispositions}
            CellComponent={DispositionLeadTableCell}
            tableMaxHeight={"calc(100vh - 270px)"}
        />
    );
};

export default DispositionLeadTable;
