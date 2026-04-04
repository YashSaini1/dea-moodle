import React from 'react';

import { COLORS } from '@colors/colors';
import { EdgeLabelProps } from '@projectTypes/types';

const { TUNA } = COLORS;

export const EdgeLabel = (props: EdgeLabelProps) => {
  const { label, transform } = props;

  return (
    <div
      className='nodrag nopan'
      style={{
        position: 'absolute',
        background: 'transparent',
        paddingBottom: 14,
        color: TUNA,
        fontSize: 12,
        fontWeight: 400,
        transform,
      }}
    >
      {label}
    </div>
  );
};
