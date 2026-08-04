import { useLeadsSelectionStore } from "@/store/leads-selection-store";
import { LoadingButton } from "@mui/lab";
import { Fragment, useState } from "react";
import toast from "react-hot-toast";
import LeadAssignUserDialog from "./LeadAssignUserDialog";
import { useEffect } from "react";

const LeadAssignComponent = ({ data, users }) => {
    const selection = useLeadsSelectionStore((state) => state.selection);

    const [open, setOpen] = useState(false);

    const [leadDatas, setLeadDatas] = useState(null);

    const handleClose = () => {
        setOpen(false);
    };

    const handleClick = () => {
        if (selection.length === 0) {
            toast.error("Please select at least one lead to assign");
        } else {
            const leadData = selection.map((id) => {
                return data.find((lead, index) => lead.id === id);
            });

            setLeadDatas(leadData);
            setOpen(true);
        }
    };

    return (
        <Fragment>
            <LoadingButton
                variant="contained"
                color="primary"
                onClick={handleClick}
            >
                Assign
            </LoadingButton>
            <LeadAssignUserDialog
                open={open}
                handleClose={handleClose}
                users={users}
                data={leadDatas}
            />
        </Fragment>
    );
};

export default LeadAssignComponent;
