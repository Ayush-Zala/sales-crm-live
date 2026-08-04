import CallDialog from "@/Call/CallDialog";
import { EmailAddress } from "@/Components/common/email-address";
import WebsiteLinkComponent from "@/Components/common/website-link-component";
import { LeadDispositionComponent } from "./CellComponents/LeadDispositionComponent";
import LeadsNameLinkComponent from "./CellComponents/LeadNameLinkComponent";

export default function LeadTableCellComponent({ row, column }) {
    switch (column.id) {
        case "name":
            return <LeadsNameLinkComponent lead={row} />;
        case "website":
            return (
                row.website && (
                    <WebsiteLinkComponent name={row?.name} url={row?.website} />
                )
            );
        case "industry":
            return row?.industry;
        case "fax":
            return row?.fax;
        case "leadPhones":
            return row?.lead_phones.map((phone, index) => (
                <CallDialog
                    key={index}
                    phone={phone.phone}
                    phoneType={phone.type?.toUpperCase() || ""}
                    name={row.name}
                    id={row.id}
                    assignedUserId={row.leadusers?.id || ""}
                    apiDataRoute="lead.windowrefreshdisposition"
                    submitDispositionRoute="lead.submitleaddisposition"
                    historyRoute="lead.getleadcallhistory"
                />
            ));
        case "leadEmails":
            return row?.lead_emails.map((email, index) => (
                <EmailAddress key={index} email={email.email} />
            ));
        case "clientPhones":
            return row?.clients.map((client) =>
                client?.lead_client_phones?.map((phone, index) => {
                    const clientName = `${client.fname ? client.fname : ""} ${
                        client.lname ? client.lname : ""
                    }`;

                    return (
                        <CallDialog
                            key={index}
                            phone={phone.phone}
                            phoneType={phone.type?.toUpperCase() || ""}
                            name={clientName}
                            id={row.id}
                            assignedUserId={row?.users[0]?.id || ""}
                            clientId={client.id}
                            apiDataRoute="lead.windowrefreshdisposition"
                            submitDispositionRoute="lead.submitleaddisposition"
                        />
                    );
                })
            );
        case "clientEmails":
            return row?.clients.map((client) =>
                client?.lead_client_emails?.map((email, index) => (
                    <EmailAddress key={index} email={email.mail} />
                ))
            );
        case "assignTo":
            return row?.users[0]?.name || "";
        case "assignBy":
            return row?.reporting_manager[0]?.name || "";
        case "leadBy":
            return row?.leadusers?.name || "";
        case "lead_source":
            return row?.lead_source || "";
        case "country":
            return row?.lead_address?.country?.name || "";
        case "state":
            return row?.lead_address?.state?.name || "";
        case "timezoneFilter":
            return row?.lead_address?.timezone || "";
        case "dispositionType":
            return <LeadDispositionComponent props={row} />;
        default:
            return null;
    }
}
