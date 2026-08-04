import { useSelectionStore } from "@/store/selection-store";
import { Button } from "@mui/material";
import React from "react";
import { Fragment } from "react";
import { useState } from "react";
import toast from "react-hot-toast";
import AssignUserDialog from "./AssignUserDialog";
import { LoadingButton } from "@mui/lab";

const AssignComponent = ({ auth, data, users = [] }) => {
    const selection = useSelectionStore((state) => state.selection);

    // remove loggedin user from the list
    const modifiedUsers = users.filter((user) => user.id !== auth.user.id);

    const [open, setOpen] = useState(false);

    const [accDatas, setAccDatas] = useState(null);

    const handleClose = () => {
        setOpen(false);
    };

    const handleClick = () => {
        if (selection.length === 0) {
            toast.error("Please select at least one account to assign");
        } else {
            const accData = selection.map((id) => {
                return data.find((account, index) => account.id === id);
            });

            setAccDatas(accData);
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
            <AssignUserDialog
                open={open}
                handleClose={handleClose}
                users={modifiedUsers}
                data={accDatas}
            />
        </Fragment>
    );
};

export default AssignComponent;
