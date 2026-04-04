import { FIELD_TEXT_RIGHT_PADDING_RIGHT, FIELDS_GAP } from '@constants/constants';
import { Widths } from '@projectTypes/types';

export const findMaxSize = (table: HTMLDivElement) => {
  const widths: Widths = { left: [], right: [], coords: [] };
  const header = table.children[0].children[0] as HTMLElement;
  const headerWidth = header.offsetWidth;
  const fields = Array.from(table.children[1].children[0].children) as HTMLElement[];

  fields.forEach((field) => {
    const leftSpan = field.children[0].children[0] as HTMLElement;
    const rightSpan = field.children[1].children[0] as HTMLElement;

    widths.coords.push(Math.floor(field.offsetTop + field.offsetHeight / 2));
    widths.left.push(leftSpan.offsetWidth);
    widths.right.push(rightSpan.offsetWidth);
  });

  const maxLeftTextWidth = Math.max.apply(null, widths.left);
  const maxRightTextWidth = Math.max.apply(null, widths.right);

  const maxHeaderWidthCondition =
    headerWidth >= maxLeftTextWidth + maxRightTextWidth + FIELDS_GAP &&
    headerWidth >= maxLeftTextWidth + maxRightTextWidth + FIELDS_GAP;

  if (maxHeaderWidthCondition) {
    return {
      maxLeft: headerWidth - maxRightTextWidth,
      maxRight: maxRightTextWidth,
      headerWidth: headerWidth,
      initCoordsForHandle: widths.coords,
    };
  } else
    return {
      maxLeft: maxLeftTextWidth,
      maxRight: maxRightTextWidth + FIELD_TEXT_RIGHT_PADDING_RIGHT,
      headerWidth: headerWidth,
      initCoordsForHandle: widths.coords,
    };
};
