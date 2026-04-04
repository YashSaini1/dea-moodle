import React, { FC, useLayoutEffect, useRef, useState } from 'react';

import '@scss/react/widgets/tablesWidget/entities/tableNode/TableNode.scss';

import { TABLE_ELEMENT_ID_PART } from '@constants/constants';
import { ITableNodeProps, IUseTableSizeReturnData } from '@projectTypes/interfaces';
import {
  getConnectionSourceTempNode,
  getIsTableDragging,
} from '@store/redux/stage/stageSelectors';
import { setIsNeedToRemoveTempLine } from '@store/redux/stage/stageSlice';
import { useAppDispatch } from '@store/redux/store';
import { useSelector } from 'react-redux';

import { findMaxSize } from './functions/findMaxSize';
import { TableField } from './tableField/TableField';

export const TableNode: FC<ITableNodeProps> = ({ data, ...props }) => {
  const { tableName, fields } = data;

  const dispatch = useAppDispatch();

  const tableRef = useRef<HTMLDivElement>(null);

  const isTableDragging = useSelector(getIsTableDragging);
  const startConnectionData = useSelector(getConnectionSourceTempNode);

  const [canTableDrag, setCanTableDrag] = useState<boolean>(false);
  const [localSizes, setLocalSizes] = useState<IUseTableSizeReturnData>({
    maxLeft: 0,
    maxRight: 0,
    headerWidth: 0,
    initCoordsForHandle: [],
  });

  useLayoutEffect(() => {
    if (!isTableDragging && tableRef.current) {
      const currentSizes = findMaxSize(tableRef.current);

      const differenceBetweenSomeSizeCondition =
        currentSizes.maxLeft !== localSizes.maxLeft ||
        currentSizes.maxRight !== localSizes.maxRight;

      if (differenceBetweenSomeSizeCondition) {
        setLocalSizes(currentSizes);
      }
    }
  });

  const onTableMouseLeave = () => {
    if (startConnectionData) {
      dispatch(setIsNeedToRemoveTempLine(true));
    }
  };

  return (
    <div
      className={`Table ${!canTableDrag ? 'nodrag' : ''}`}
      id={`${TABLE_ELEMENT_ID_PART}${props.id}`}
      /*onMouseLeave={onTableMouseLeave}*/
      ref={tableRef}
    >
      <div
        className='TableHeader'
        onMouseEnter={() => setCanTableDrag(true)}
        onMouseLeave={() => setCanTableDrag(false)}
      >
        <span>{tableName}</span>
      </div>
      <div className='TableContentContainer'>
        <ul className='TableFieldsContainer'>
          {fields.map((field, index) => (
            <TableField
              field={field}
              fieldIndex={index}
              key={field.id}
              sizes={localSizes}
              tableId={props.id}
            />
          ))}
        </ul>
      </div>
    </div>
  );
};
