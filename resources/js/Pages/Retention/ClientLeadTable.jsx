import SimpleDataTable from "@/Components/SimpleDataTable";
import ClientLeadTableCell from "./ClientLeadTableCell";

export default function ClientLeadTable({ clients }) {
    const columns = [
        { id: "clientname", label: "Name" },
        { id: "phones", label: "Phone" },
        { id: "emails", label: "Email" },
        { id: "designation", label: "Designation" },
        { id: "linkedinurl", label: "Linkedin URL" },
    ];

    return (
        <SimpleDataTable
            columns={columns}
            rows={clients}
            CellComponent={ClientLeadTableCell}
            tableMaxHeight={"calc(100vh - 370px)"}
        />
    );
}
