import CallDialog from "@/Call/CallDialog";
import { EmailAddress } from "@/Components/common/email-address";
import { formatDateTime } from "@/utils/date-time-formatters";
import { LeadDispositionComponent } from "./CellComponents/LeadDispositionComponent";
import LeadsNameLinkComponent from "./CellComponents/LeadNameLinkComponent";

export default function LeadTableCellComponent({ row, column }) {
    switch (column.id) {
        case "name":
            return <LeadsNameLinkComponent lead={row} />;
        case "lastOrderDate":
            return (
                row.last_order_us_date && formatDateTime(row.last_order_us_date)
            );
        case "industry":
            return row?.industry;
        case "description":
            return row?.description;
        case "retentionPhones":
            return row?.retention_phone?.map((phone, index) => (
                <CallDialog
                    key={index}
                    phone={phone.phone}
                    name={row.name}
                    id={row.id}
                    assignedUserId={row.leadusers?.id || ""}
                    apiDataRoute="retention.windowrefreshdisposition"
                    submitDispositionRoute="retention.submitleaddisposition"
                    historyRoute="retention.getleadcallhistory"
                />
            ));
        case "retentionEmails":
            return row?.retention_emails?.map((email, index) => (
                <EmailAddress key={index} email={email.email} />
            ));
        case "clientPhones":
            return row?.clients.map((client) =>
                client?.client_phones?.map((phone, index) => {
                    const clientName = `${client.fname ? client.fname : ""} ${
                        client.lname ? client.lname : ""
                    }`;

                    return (
                        <CallDialog
                            key={index}
                            phone={phone.phone}
                            phoneType={phone.type}
                            name={clientName}
                            id={row.id}
                            assignedUserId={row?.assignTo || ""}
                            clientId={client.id}
                            apiDataRoute="retention.windowrefreshdisposition"
                            submitDispositionRoute="retention.submitleaddisposition"
                            historyRoute="retention.getleadcallhistory"
                        />
                    );
                })
            );
        case "clientEmails":
            return row?.clients.map((client) =>
                client?.client_emails?.map((email, index) => (
                    <EmailAddress key={index} email={email.mail} />
                ))
            );
        case "clientName":
            return row?.clients[0]?.fname + " " + row?.clients[0]?.lname || "";
        case "assignTo":
            return row?.assignTo?.name || "";
        case "assignBy":
            return row?.assignBy?.name || "";
        case "leadBy":
            return row?.lead_provide_by || "";
        case "country":
            return row?.lead_address?.country || "";
        case "state":
            return row?.lead_address?.state || "";
        case "timezoneFilter":
            return row?.lead_address?.timezone || "";
        case "dispositionType":
            return <LeadDispositionComponent props={row} />;
        default:
            return null;
    }
}
