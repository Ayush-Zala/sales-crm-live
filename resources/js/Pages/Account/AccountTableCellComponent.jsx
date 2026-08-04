import CallDialog from "@/Call/CallDialog";
import { EmailAddress } from "@/Components/common/email-address";
import WebsiteLinkComponent from "@/Components/common/website-link-component";
import AccountNameLinkComponent from "./CellComponents/AccountNameLinkComponent";
import { DispositionComponent } from "./CellComponents/DispositionComponent";

export default function AccountTableCellComponent({ row, column }) {
    switch (column.id) {
        case "name":
            return <AccountNameLinkComponent account={row} />;
        case "website":
            return (
                row.website && (
                    <WebsiteLinkComponent name={row?.name} url={row?.website} />
                )
            );
        case "fax":
            return row?.fax;
        case "companyPhones":
            return row?.companyPhones?.map((phone, index) => {
                const dontShowDisposition =
                    row.disposition?.phone === phone.phone &&
                    (row.disposition?.status === "Do Not Call" ||
                        row.disposition?.status === "Number Not In Service");

                return (
                    <CallDialog
                        key={index}
                        phone={phone.phone}
                        phoneType={phone.type}
                        name={row.name}
                        id={row.id}
                        assignedUserId={row?.assignTo?.id || ""}
                        dontShowNo={row.blacklisted || dontShowDisposition}
                    />
                );
            });
        case "companyEmails":
            return row?.companyEmails?.map((email, index) => (
                <EmailAddress key={index} email={email.email} />
            ));
        case "clientPhones":
            return (
                row.clients &&
                row.clients.map((client) =>
                    client?.clientPhones?.map((phone, index) => {
                        const clientName = `${
                            client.fname ? client.fname : ""
                        } ${client.lname ? client.lname : ""}`;

                        const dontShowDis =
                            row.disposition?.phone === phone.phone &&
                            (row.disposition?.status === "Do Not Call" ||
                                row.disposition?.status ===
                                    "Number Not In Service");

                        return (
                            <CallDialog
                                key={index}
                                phone={phone.phone}
                                phoneType={phone.type}
                                name={clientName}
                                id={row.id}
                                assignedUserId={row?.assignTo?.id || ""}
                                clientId={client.id}
                                dontShowNo={client.blacklisted || dontShowDis}
                            />
                        );
                    })
                )
            );
        case "clientEmails":
            return (
                row.clients &&
                row.clients.map((client) =>
                    client?.clientEmails?.map((email, index) => (
                        <EmailAddress key={index} email={email.mail} />
                    ))
                )
            );
        case "assignTo":
            return row?.assignTo?.name || "";
        case "assignBy":
            return row?.assignBy?.name || "";
        case "industry":
            return row?.industry;
        case "source":
            return row?.source;
        case "country":
            return row?.companyAddress?.country || "";
        case "state":
            return row?.companyAddress?.state || "";
        case "timezone":
            return row?.companyAddress?.timezone || "";
        case "dispositionDate":
            return <DispositionComponent disposition={row.disposition} />;
        default:
            return null;
    }
}
