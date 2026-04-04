import React from 'react';

import { IFieldHandleProps } from '@projectTypes/interfaces';
import { getSourceTableId } from '@store/redux/stage/stageSelectors';
import { useSelector } from 'react-redux';
import { Handle } from 'reactflow';

export const FieldHandle = (props: IFieldHandleProps) => {
  const { tableId, style, id, position } = props;

  const sourceTableId = useSelector(getSourceTableId);

  const isSource = sourceTableId === '' || sourceTableId === tableId;
  const isTarget = sourceTableId !== '' || sourceTableId !== tableId;

  return (
    <div className='FieldHandle'>
      <div style={{ display: isSource ? 'block' : 'none' }}>
        <Handle
          id={id}
          isConnectable={true}
          position={position}
          style={style}
          type='source'
        />
      </div>

      <div style={{ display: isTarget ? 'block' : 'none' }}>
        <Handle
          id={id}
          isConnectable={true}
          position={position}
          style={style}
          type='target'
        />
      </div>
    </div>
  );
};
