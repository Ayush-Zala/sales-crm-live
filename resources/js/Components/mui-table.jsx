import { Table } from '@mui/material'
import { forwardRef } from 'react'

export const MuiTable = forwardRef((props, ref) => {
  return (
    <Table className='data-table-root' ref={ref} {...props}>
      {props.children}
    </Table>
  )
})
