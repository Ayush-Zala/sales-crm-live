import { yupResolver } from "@hookform/resolvers/yup";
import { LoadingButton } from "@mui/lab";
import { Stack } from "@mui/material";
import { TextFieldElement, useForm } from "react-hook-form-mui";
import toast from "react-hot-toast";
import * as yup from "yup";

const TargetAssignToUser = ({ rowData }) => {
    const defaultValues = {
        targetValue: rowData.target_value,
    };

    const schema = yup.object().shape({
        targetValue: yup.number().required().min(0),
    });

    const { handleSubmit, control } = useForm({
        defaultValues,
        resolver: yupResolver(schema),
    });

    const submit = (data) => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route("target.updatetarget"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
                target_id: rowData.id,
                target_value: data.targetValue,
                user_id: rowData.user_id,
                time: rowData.time,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                toast.success("Target assigned successfully");
            })
            .catch((error) => {
                toast.error("Error assigning target");
            });
    };

    return (
        <Stack direction="row" spacing={2}>
            <TextFieldElement
                control={control}
                name="targetValue"
                type="number"
                inputProps={{
                    min: 0, // Prevent user from typing numbers less than 0
                    pattern: "[0-9]*", // Allow only numeric input
                }}
            />
            <LoadingButton onClick={handleSubmit(submit)}>Assign</LoadingButton>
        </Stack>
    );
};

export default TargetAssignToUser;
