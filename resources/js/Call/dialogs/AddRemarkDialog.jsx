import { yupResolver } from "@hookform/resolvers/yup";
import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    IconButton,
    Stack,
    Tooltip,
} from "@mui/material";
import { StickyNote } from "lucide-react";
import { useState } from "react";
import {
    FormContainer,
    RadioButtonGroup,
    TextFieldElement,
    useForm,
} from "react-hook-form-mui";
import toast from "react-hot-toast";
import * as Yup from "yup";

const AddRemarkDialog = ({
    companyId,
    clientId,
    phone,
    addremarkUrl = "account.addremark",
}) => {
    const [remarkDialog, setRemarkDialog] = useState(false);

    const handleOpenRemarkDialog = () => setRemarkDialog(true);

    const handleClose = () => setRemarkDialog(false);

    const defaultValues = {
        type: "incoming",
        remark: "",
    };

    const schema = Yup.object().shape({
        type: Yup.string().required("Call Type is required"),
        remark: Yup.string().required("remarks is required"),
    });

    const { control, handleSubmit, reset } = useForm({
        defaultValues,
        resolver: yupResolver(schema),
    });

    const submit = (data) => {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        fetch(route(addremarkUrl), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({ ...data, companyId, clientId, phone }),
        })
            .then((response) => response.json())
            .then((res) => {
                toast.success(res.message);
                handleClose();
                reset();
            })
            .catch((error) => {
                console.error(error);
                toast.error("An error occurred. Please try again.");
            });
    };

    return (
        <>
            <Tooltip arrow placement="right" title="Add Remarks">
                <IconButton
                    color="success"
                    size="small"
                    onClick={handleOpenRemarkDialog}
                >
                    <StickyNote size={18} />
                </IconButton>
            </Tooltip>
            <Dialog
                open={remarkDialog}
                aria-labelledby="alert-dialog-title"
                aria-describedby="alert-dialog-description"
                onClose={(_, reason) =>
                    reason !== "backdropClick" && handleClose()
                }
                fullWidth
            >
                <FormContainer
                    control={control}
                    onSuccess={handleSubmit(submit)}
                >
                    <DialogTitle id="alert-dialog-title">
                        Add Remark
                    </DialogTitle>
                    <DialogContent dividers>
                        <Stack direction="column" spacing={1}>
                            <RadioButtonGroup
                                row
                                required
                                name="type"
                                control={control}
                                options={[
                                    { value: "incoming", label: "Incoming" },
                                    { value: "outgoing", label: "Outgoing" },
                                ]}
                                labelKey="label"
                                valueKey="value"
                            />
                            <TextFieldElement
                                required
                                name="remark"
                                label="Remarks"
                                control={control}
                                InputProps={{
                                    multiline: true,
                                    rows: 4,
                                }}
                            />
                        </Stack>
                    </DialogContent>
                    <DialogActions>
                        <Button onClick={handleClose} color="error">
                            Cancel
                        </Button>
                        <Button type="submit">Add</Button>
                    </DialogActions>
                </FormContainer>
            </Dialog>
        </>
    );
};

export default AddRemarkDialog;
