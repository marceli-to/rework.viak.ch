<template>
	<div>
  <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" v-if="$props.size == 'sm'">
    <path fill="currentColor" d="M14.759 0L7.978 6.781 1.285.044.044 1.285l6.692 6.738L0 14.759l1.152 1.197 6.781-6.781L14.759 16 16 14.759 9.175 7.934l6.736-6.737z"/>
  </svg>
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" v-else-if="$props.size == 'lg'">
    <path fill="currentColor" d="M29.9 0L16.4 13.4 2.8 0 0 2.8l13.6 13.5L.9 29l2.7 3 12.8-12.8L29 32l2.8-3-12.7-12.7L32.7 2.8z"/>
  </svg>
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 22" v-else>
    <path fill="currentColor" d="M20.116 0L11 9.01 1.886 0 0 1.886 9.166 11l-8.59 8.538L2.41 21.53 11 12.939l8.54 8.591 1.886-1.992L12.834 11 22 1.886z"/>
  </svg>
</div>
</template>
