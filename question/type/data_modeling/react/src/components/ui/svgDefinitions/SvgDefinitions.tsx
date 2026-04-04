import React from 'react';

import { COLORS } from '@colors/colors';
import { SVG_MARKER_ARROW_ID, SVG_MARKER_LINE_ID } from '@constants/constants';

const { ATHENS_GRAY } = COLORS;

export const SvgDefinitions = () => {
  return (
    <svg style={{ position: 'absolute', top: 0, left: 0 }}>
      <defs>
        <marker
          id={SVG_MARKER_LINE_ID}
          markerHeight={6}
          markerWidth={14}
          orient='auto-start-reverse'
          refX={13}
          refY={3}
          viewBox='0 0 14 6'
        >
          <path
            d='M 9.0199955,4.2701003 V 8.2679451'
            stroke={ATHENS_GRAY}
            strokeLinecap='round'
            strokeWidth='1'
            transform='translate(-2.0199955,-3.2701003)'
          />
        </marker>

        <marker
          id={SVG_MARKER_ARROW_ID}
          markerHeight={20}
          markerWidth={8}
          orient='auto-start-reverse'
          refX={8}
          refY={8.82}
          viewBox='0 0 8 11'
        >
          <path
            d='M 3.0239016,8.7975942 9.0137235,5.7741331'
            stroke={ATHENS_GRAY}
            strokeLinecap='round'
            strokeLinejoin='round'
            strokeWidth='1'
          />
          <path
            d='M 9.0137235,11.821055 3.0239016,8.7975942'
            stroke={ATHENS_GRAY}
            strokeLinecap='round'
            strokeLinejoin='round'
            strokeWidth='1'
          />
        </marker>
      </defs>
    </svg>
  );
};
